<?php

namespace App\Services;

use App\Models\Bloqueo;
use App\Models\Box;
use App\Models\Cita;
use App\Models\Doctor;
use App\Models\Horario;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CitaService
{
    public const HORA_APERTURA = '08:00';

    public const HORA_CIERRE = '20:00';

    public const TOLERANCIA_MINUTOS = 15;

    private const TRANSICIONES = [
        'agendada' => ['confirmada', 'atendida', 'cancelada', 'no_asistio'],
        'reagendada' => ['confirmada', 'atendida', 'cancelada', 'no_asistio'],
        'confirmada' => ['atendida', 'cancelada', 'no_asistio'],
        'atendida' => [],
        'cancelada' => [],
        'no_asistio' => [],
    ];

    public static function pendienteDeCierre(Cita $cita): bool
    {
        return in_array($cita->estado, Cita::ESTADOS_VIGENTES, true)
            && $cita->hora_inicio->copy()->addMinutes(self::TOLERANCIA_MINUTOS)->lte(now());
    }

    public function agendar(array $datos): Cita
    {
        $cita = new Cita(['paciente_id' => $datos['paciente_id'], 'estado' => 'agendada']);

        return $this->guardar($cita, $datos, 'crear');
    }

    public function reagendar(Cita $cita, array $datos): Cita
    {
        if (! in_array($cita->estado, ['agendada', 'reagendada', 'confirmada'], true)) {
            throw ValidationException::withMessages(['hora' => 'Solo se pueden reagendar citas vigentes.']);
        }

        $cita->estado = 'reagendada';

        return $this->guardar($cita, $datos, 'reagendar');
    }

    public function cambiarEstado(Cita $cita, string $estado, ?string $motivo = null): void
    {
        if (! in_array($estado, self::TRANSICIONES[$cita->estado], true)) {
            throw ValidationException::withMessages([
                'estado' => 'Una cita '.mb_strtolower($cita->etiqueta_estado).' no puede pasar a '.mb_strtolower(Cita::ETIQUETAS[$estado] ?? $estado).'.',
            ]);
        }

        if ($estado === 'no_asistio' && ! self::pendienteDeCierre($cita)) {
            throw ValidationException::withMessages([
                'estado' => 'La inasistencia se marca '.self::TOLERANCIA_MINUTOS.' minutos después de la hora de la cita.',
            ]);
        }

        $antes = ['estado' => $cita->estado];
        $cita->update(['estado' => $estado]);

        $despues = ['estado' => $estado];
        if ($motivo !== null) {
            $despues['motivo'] = $motivo;
        }

        $accion = match ($estado) {
            'cancelada' => 'cancelar',
            'no_asistio' => 'marcar_inasistencia',
            default => 'cambiar_estado',
        };

        AuditoriaService::registrar($accion, 'citas', $cita->id, $antes, $despues);
    }

    private function guardar(Cita $cita, array $datos, string $accion): Cita
    {
        $inicio = Carbon::parse($datos['fecha'].' '.$datos['hora']);
        $fin = $inicio->copy()->addMinutes((int) $datos['duracion']);

        try {
            return DB::transaction(function () use ($cita, $datos, $inicio, $fin, $accion) {
                $doctor = Doctor::with('usuario')->lockForUpdate()->findOrFail($datos['doctor_id']);
                $box = Box::lockForUpdate()->findOrFail($datos['box_id']);

                $this->validarDisponibilidad($cita, $doctor, $box, $inicio, $fin);

                $antes = $cita->exists ? $this->resumen($cita) : null;

                $cita->fill([
                    'doctor_id' => $doctor->id,
                    'box_id' => $box->id,
                    'hora_inicio' => $inicio,
                    'hora_fin' => $fin,
                ])->save();

                AuditoriaService::registrar($accion, 'citas', $cita->id, $antes, $this->resumen($cita));

                return $cita;
            });
        } catch (QueryException $e) {
            if ($e->getCode() === '23P01') {
                throw ValidationException::withMessages(['hora' => 'El horario acaba de ser ocupado por otra reserva. Elija otro.']);
            }

            throw $e;
        }
    }

    private function validarDisponibilidad(Cita $cita, Doctor $doctor, Box $box, Carbon $inicio, Carbon $fin): void
    {
        if (! $doctor->activo) {
            $this->rechazar('doctor_id', "{$doctor->nombre} no está habilitado para agendar.");
        }

        if (! $box->activo) {
            $this->rechazar('box_id', "{$box->nombre} no está habilitado.");
        }

        if ($inicio->isPast()) {
            $this->rechazar('hora', 'No se puede agendar en un horario que ya pasó.');
        }

        $dentroDelHorario = Horario::where('doctor_id', $doctor->id)
            ->where('dia_semana', $inicio->dayOfWeekIso)
            ->where('hora_inicio', '<=', $inicio->format('H:i:s'))
            ->where('hora_fin', '>=', $fin->format('H:i:s'))
            ->exists();

        if (! $dentroDelHorario) {
            $this->rechazar('hora', "La cita queda fuera del horario de atención de {$doctor->nombre}.");
        }

        $bloqueo = Bloqueo::with(['doctor.usuario', 'box'])
            ->enFecha($inicio)
            ->queAfectan($doctor->id, $box->id)
            ->first();

        if ($bloqueo) {
            $this->rechazar('hora', "No se puede agendar. {$bloqueo->alcance} tiene un bloqueo {$bloqueo->periodo}: {$bloqueo->motivo}.");
        }

        $cruceDoctor = $this->buscarCruce('doctor_id', $doctor->id, $inicio, $fin, $cita);
        if ($cruceDoctor) {
            $this->rechazar('hora', "No se puede agendar. {$doctor->nombre} ya tiene una cita entre "
                .$cruceDoctor->hora_inicio->format('H:i').' y '.$cruceDoctor->hora_fin->format('H:i').'.');
        }

        $cruceBox = $this->buscarCruce('box_id', $box->id, $inicio, $fin, $cita);
        if ($cruceBox) {
            $this->rechazar('hora', "No se puede agendar. {$box->nombre} está ocupado entre "
                .$cruceBox->hora_inicio->format('H:i').' y '.$cruceBox->hora_fin->format('H:i').'.');
        }
    }

    private function buscarCruce(string $columna, int $id, Carbon $inicio, Carbon $fin, Cita $cita): ?Cita
    {
        return Cita::where($columna, $id)
            ->whereNotIn('estado', Cita::ESTADOS_LIBERAN_HORARIO)
            ->when($cita->exists, fn ($q) => $q->where('id', '<>', $cita->id))
            ->where('hora_inicio', '>', $inicio->copy()->subMinutes(60))
            ->where('hora_inicio', '<', $fin)
            ->where('hora_fin', '>', $inicio)
            ->first();
    }

    private function rechazar(string $campo, string $mensaje): never
    {
        throw ValidationException::withMessages([$campo => $mensaje]);
    }

    private function resumen(Cita $cita): array
    {
        return [
            'doctor_id' => $cita->doctor_id,
            'box_id' => $cita->box_id,
            'hora_inicio' => $cita->hora_inicio->format('Y-m-d H:i'),
            'hora_fin' => $cita->hora_fin->format('Y-m-d H:i'),
            'estado' => $cita->estado,
        ];
    }
}
