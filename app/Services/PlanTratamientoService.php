<?php

namespace App\Services;

use App\Models\DetallePresupuesto;
use App\Models\Evolucion;
use App\Models\Paciente;
use App\Models\Usuario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlanTratamientoService
{
    public const ESTADOS_CON_PLAN = ['aceptado', 'pagado'];

    public function planDelPaciente(Paciente $paciente): Collection
    {
        return $paciente->presupuestos()
            ->with(['doctor.usuario', 'detalles.tratamiento'])
            ->whereIn('estado', self::ESTADOS_CON_PLAN)
            ->orderBy('created_at')
            ->get()
            ->map(function ($presupuesto) {
                $total = $presupuesto->detalles->sum('cantidad');
                $hechos = $presupuesto->detalles->sum('cantidad_realizada');

                return [
                    'presupuesto' => $presupuesto,
                    'total' => $total,
                    'realizados' => $hechos,
                    'avance' => $total > 0 ? (int) round($hechos / $total * 100) : 0,
                ];
            });
    }

    public function registrarRealizado(DetallePresupuesto $detalle, Usuario $usuario): Evolucion
    {
        return DB::transaction(function () use ($detalle, $usuario) {
            $detalle = DetallePresupuesto::with(['presupuesto.paciente.ficha', 'tratamiento'])
                ->lockForUpdate()
                ->findOrFail($detalle->id);
            $presupuesto = $detalle->presupuesto;

            if (! in_array($presupuesto->estado, self::ESTADOS_CON_PLAN, true)) {
                throw ValidationException::withMessages(['detalle' => 'Solo se registran tratamientos de presupuestos aceptados o pagados.']);
            }

            if ($detalle->pendientes() === 0) {
                throw ValidationException::withMessages(['detalle' => 'Este tratamiento ya está completo.']);
            }

            $antes = $detalle->cantidad_realizada;
            $detalle->update(['cantidad_realizada' => $antes + 1]);

            $evolucion = Evolucion::create([
                'ficha_id' => $presupuesto->paciente->ficha->id,
                'doctor_id' => $usuario->doctor?->id ?? $presupuesto->doctor_id,
                'detalle_presupuesto_id' => $detalle->id,
                'fecha' => today(),
                'diagnostico' => 'Plan de tratamiento del presupuesto N.° '.$presupuesto->id,
                'tratamiento_realizado' => $detalle->tratamiento->nombre.' ('.$detalle->cantidad_realizada.' de '.$detalle->cantidad.')',
            ]);

            AuditoriaService::registrar('registrar_realizado', 'detalle_presupuestos', $detalle->id,
                ['cantidad_realizada' => $antes], ['cantidad_realizada' => $detalle->cantidad_realizada]);

            return $evolucion;
        });
    }
}
