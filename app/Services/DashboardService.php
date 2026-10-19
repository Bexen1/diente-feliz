<?php

namespace App\Services;

use App\Models\Box;
use App\Models\Cita;
use App\Models\Pago;
use App\Models\Presupuesto;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function indicadores(Carbon $mes): array
    {
        $desde = $mes->copy()->startOfMonth();
        $hasta = $mes->copy()->endOfMonth();

        return [
            'sillones' => $this->sillonesHoy(),
            'presupuestos' => $this->presupuestosAceptados($desde, $hasta),
            'caja' => $this->caja($desde, $hasta),
            'porEspecialista' => $this->ingresosPorEspecialista($desde, $hasta),
            'citasPorEstado' => $this->citasPorEstado($desde, $hasta),
        ];
    }

    private function sillonesHoy(): array
    {
        $ahora = now();
        $boxActivos = Box::where('activo', true)->count();

        $ocupadosAhora = Cita::where('estado', '<>', 'cancelada')
            ->where('hora_inicio', '<=', $ahora)
            ->where('hora_fin', '>', $ahora)
            ->distinct('box_id')
            ->count('box_id');

        $minutosAgendados = Cita::where('estado', '<>', 'cancelada')
            ->whereDate('hora_inicio', $ahora->toDateString())
            ->get(['hora_inicio', 'hora_fin'])
            ->sum(fn ($c) => $c->hora_inicio->diffInMinutes($c->hora_fin));

        $minutosDisponibles = $boxActivos * Carbon::parse(CitaService::HORA_APERTURA)->diffInMinutes(Carbon::parse(CitaService::HORA_CIERRE));

        return [
            'activos' => $boxActivos,
            'ocupadosAhora' => $ocupadosAhora,
            'ocupacionDia' => $minutosDisponibles > 0 ? round($minutosAgendados / $minutosDisponibles * 100) : 0,
        ];
    }

    private function presupuestosAceptados(Carbon $desde, Carbon $hasta): array
    {
        $consulta = Presupuesto::whereIn('estado', ['aceptado', 'pagado'])->whereBetween('created_at', [$desde, $hasta]);

        return [
            'cantidad' => (clone $consulta)->count(),
            'monto' => (float) (clone $consulta)->sum('total_calculado'),
            'borradores' => Presupuesto::where('estado', 'borrador')->count(),
        ];
    }

    private function caja(Carbon $desde, Carbon $hasta): array
    {
        $porCobrar = Presupuesto::where('estado', 'aceptado')
            ->withSum(['pagos as pagado' => fn ($q) => $q->where('estado', 'vigente')], 'monto')
            ->get()
            ->sum(fn ($p) => (float) $p->total_calculado - (float) $p->pagado);

        return [
            'hoy' => (float) Pago::where('estado', 'vigente')->whereDate('fecha', today())->sum('monto'),
            'mes' => (float) Pago::where('estado', 'vigente')->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])->sum('monto'),
            'porCobrar' => $porCobrar,
        ];
    }

    private function ingresosPorEspecialista(Carbon $desde, Carbon $hasta): array
    {
        return DB::table('pagos')
            ->join('presupuestos', 'presupuestos.id', '=', 'pagos.presupuesto_id')
            ->join('doctores', 'doctores.id', '=', 'presupuestos.doctor_id')
            ->join('usuarios', 'usuarios.id', '=', 'doctores.usuario_id')
            ->where('pagos.estado', 'vigente')
            ->whereBetween('pagos.fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->groupBy('usuarios.nombre', 'doctores.especialidad')
            ->orderByDesc('total')
            ->get(['usuarios.nombre', 'doctores.especialidad', DB::raw('SUM(pagos.monto) AS total')])
            ->map(fn ($fila) => (array) $fila)
            ->all();
    }

    private function citasPorEstado(Carbon $desde, Carbon $hasta): array
    {
        return Cita::whereBetween('hora_inicio', [$desde, $hasta])
            ->selectRaw('estado, COUNT(*) AS total')
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->all();
    }
}
