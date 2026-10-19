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
        ];

        if ($usuario->tieneRol('Recepcionista')) {
            $alertas['sinConfirmar'] = $this->citasSinConfirmarManana();
            $alertas['saldosPendientes'] = $this->presupuestosConSaldo();
        }

        return $alertas;
    }

    private function citasDelDia(string $fecha, Usuario $usuario): Collection
    {
        $consulta = Cita::with(['paciente', 'doctor.usuario', 'box'])
            ->whereDate('hora_inicio', $fecha)
            ->where('estado', '<>', 'cancelada')
            ->orderBy('hora_inicio');

        if ($usuario->esDentista()) {
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
