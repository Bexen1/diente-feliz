<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Presupuesto;
use App\Models\Usuario;
use Illuminate\Support\Collection;

class AlertaService
{
    public function paraUsuario(Usuario $usuario): array
    {
        $alertas = [
            'citasHoy' => $this->citasDelDia(now()->toDateString(), $usuario),
            'pendientesCierre' => $this->pendientesDeCierre($usuario),
        ];

        if ($usuario->veTodaLaAgenda()) {
            $alertas['sinConfirmar'] = $this->citasSinConfirmarManana();
        }

        if ($usuario->puede('pagos.registrar')) {
            $alertas['saldosPendientes'] = $this->presupuestosConSaldo();
        }

        return $alertas;
    }

    private function citasDelDia(string $fecha, Usuario $usuario): Collection
    {
        $consulta = Cita::with(['paciente', 'doctor.usuario', 'box'])
            ->whereDate('hora_inicio', $fecha)
            ->whereNotIn('estado', Cita::ESTADOS_LIBERAN_HORARIO)
            ->orderBy('hora_inicio');

        if (! $usuario->veTodaLaAgenda()) {
            $consulta->where('doctor_id', $usuario->doctor?->id);
        }

        return $consulta->get();
    }

    private function pendientesDeCierre(Usuario $usuario): Collection
    {
        $consulta = Cita::with(['paciente', 'doctor.usuario'])
            ->whereIn('estado', Cita::ESTADOS_VIGENTES)
            ->where('hora_inicio', '<=', now()->subMinutes(CitaService::TOLERANCIA_MINUTOS))
            ->orderBy('hora_inicio');

        if (! $usuario->veTodaLaAgenda()) {
            $consulta->where('doctor_id', $usuario->doctor?->id);
        }

        return $consulta->get();
    }

    private function citasSinConfirmarManana(): Collection
    {
        return Cita::with(['paciente', 'doctor.usuario'])
            ->whereDate('hora_inicio', now()->addDay()->toDateString())
            ->whereIn('estado', ['agendada', 'reagendada'])
            ->orderBy('hora_inicio')
            ->get();
    }

    private function presupuestosConSaldo(): Collection
    {
        return Presupuesto::with('paciente')
            ->where('estado', 'aceptado')
            ->withSum(['pagos as pagado' => fn ($q) => $q->where('estado', 'vigente')], 'monto')
            ->get()
            ->filter(fn ($p) => (float) $p->total_calculado - (float) $p->pagado > 0);
    }
}
