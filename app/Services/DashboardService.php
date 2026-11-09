<?php

namespace App\Services;

use App\Models\Box;
use App\Models\Cita;
use App\Models\Horario;
use App\Models\Paciente;
use App\Models\Pago;
use App\Models\Presupuesto;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public const DIAS_SALDO_ANTIGUO = 30;

    public function indicadores(Carbon $mes): array
    {
        $desde = $mes->copy()->startOfMonth();
        $hasta = $mes->copy()->endOfMonth();

        return [
            'actualizado' => now(),
            'sillones' => $this->sillonesHoy(),
            'presupuestos' => $this->presupuestosAceptados($desde, $hasta),
            'caja' => $this->caja($desde, $hasta),
            'porEspecialista' => $this->ingresosPorEspecialista($desde, $hasta),
            'citasPorEstado' => $this->citasPorEstado($desde, $hasta),
            'ingresosMensuales' => $this->ingresosMensuales($desde),
            'aceptacion' => $this->aceptacion($desde, $hasta),
            'descuentos' => $this->descuentosPorConvenio($desde, $hasta),
            'saldosAntiguos' => $this->saldosAntiguos(),
            'pacientesPorConvenio' => $this->pacientesPorConvenio(),
            'pacientesMes' => $this->pacientesDelMes($desde, $hasta),
            'tratamientos' => $this->tratamientosMasSolicitados($desde, $hasta),
            'ocupacionBox' => $this->ocupacionPorBox($desde, $hasta),
            'cancelacion' => $this->cancelacion($desde, $hasta),
            'demanda' => $this->demanda($desde, $hasta),
            'sucursales' => $this->actividadPorSucursal($desde, $hasta),
            'porMetodo' => $this->ingresosPorMetodo($desde, $hasta),
        ];
    }

    private function ingresosPorMetodo(Carbon $desde, Carbon $hasta): array
    {
        $totales = Pago::where('estado', 'vigente')
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->selectRaw('metodo, COUNT(*) AS pagos, SUM(monto) AS total')
            ->groupBy('metodo')
            ->get()
            ->keyBy('metodo');

        return collect(Pago::METODOS)
            ->map(fn ($nombre, $clave) => [
                'metodo' => $nombre,
                'pagos' => (int) ($totales[$clave]->pagos ?? 0),
                'total' => (float) ($totales[$clave]->total ?? 0),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    private function minutosDeAtencionPorDia(): int
    {
        return (int) Carbon::parse(CitaService::HORA_APERTURA)->diffInMinutes(Carbon::parse(CitaService::HORA_CIERRE));
    }

    private function sillonesHoy(): array
    {
        $ahora = now();
        $boxActivos = Box::where('activo', true)->count();

        $ocupadosAhora = Cita::whereNotIn('estado', Cita::ESTADOS_LIBERAN_HORARIO)
            ->where('hora_inicio', '<=', $ahora)
            ->where('hora_fin', '>', $ahora)
            ->distinct('box_id')
            ->count('box_id');

        $minutosAgendados = Cita::whereNotIn('estado', Cita::ESTADOS_LIBERAN_HORARIO)
            ->whereDate('hora_inicio', $ahora->toDateString())
            ->get(['hora_inicio', 'hora_fin'])
            ->sum(fn ($c) => $c->hora_inicio->diffInMinutes($c->hora_fin));

        $minutosDisponibles = $boxActivos * $this->minutosDeAtencionPorDia();

        return [
            'activos' => $boxActivos,
            'ocupadosAhora' => $ocupadosAhora,
            'ocupacionDia' => $minutosDisponibles > 0 ? (int) round($minutosAgendados / $minutosDisponibles * 100) : 0,
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
            ->map(fn ($fila) => ['nombre' => $fila->nombre, 'especialidad' => $fila->especialidad, 'total' => (float) $fila->total])
            ->all();
    }

    private function citasPorEstado(Carbon $desde, Carbon $hasta): array
    {
        return Cita::whereBetween('hora_inicio', [$desde, $hasta])
            ->selectRaw('estado, COUNT(*) AS total')
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    private function ingresosMensuales(Carbon $desde): array
    {
        $inicio = $desde->copy()->subMonths(5);
        $fin = $desde->copy()->endOfMonth();

        $totales = DB::table('pagos')
            ->where('estado', 'vigente')
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->selectRaw("to_char(fecha, 'YYYY-MM') AS periodo, SUM(monto) AS total")
            ->groupBy('periodo')
            ->pluck('total', 'periodo');

        $meses = [];
        for ($i = 0; $i < 6; $i++) {
            $mes = $inicio->copy()->addMonths($i);
            $meses[] = [
                'etiqueta' => ucfirst(rtrim($mes->locale('es')->isoFormat('MMM'), '.')),
                'total' => (float) ($totales[$mes->format('Y-m')] ?? 0),
            ];
        }

        $hoy = today();
        if ($desde->isSameMonth($hoy)) {
            $inicioAnterior = $desde->copy()->subMonth();
            $finAnterior = $inicioAnterior->copy()->addDays($hoy->day - 1);
            if ($finAnterior->gt($inicioAnterior->copy()->endOfMonth())) {
                $finAnterior = $inicioAnterior->copy()->endOfMonth();
            }

            $actual = $this->sumaPagos($desde, $hoy);
            $anterior = $this->sumaPagos($inicioAnterior, $finAnterior);
            $comparacion = 'mismo período del mes anterior';
        } else {
            $actual = $meses[5]['total'];
            $anterior = $meses[4]['total'];
            $comparacion = 'mes anterior';
        }

        return [
            'meses' => $meses,
            'maximo' => max(array_column($meses, 'total')),
            'variacion' => $anterior > 0 ? (int) round(($actual - $anterior) / $anterior * 100) : null,
            'comparacion' => $comparacion,
        ];
    }

    private function sumaPagos(Carbon $desde, Carbon $hasta): float
    {
        return (float) Pago::where('estado', 'vigente')
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->sum('monto');
    }

    private function aceptacion(Carbon $desde, Carbon $hasta): array
    {
        $emitidos = Presupuesto::whereBetween('created_at', [$desde, $hasta])->count();
        $aceptados = Presupuesto::whereBetween('created_at', [$desde, $hasta])->whereIn('estado', ['aceptado', 'pagado']);

        $cantidad = (clone $aceptados)->count();

        return [
            'emitidos' => $emitidos,
            'aceptados' => $cantidad,
            'tasa' => $emitidos > 0 ? (int) round($cantidad / $emitidos * 100) : 0,
            'promedio' => (float) ((clone $aceptados)->avg('total_calculado') ?? 0),
        ];
    }

    private function descuentosPorConvenio(Carbon $desde, Carbon $hasta): array
    {
        $lineas = DB::table('detalle_presupuestos')
            ->selectRaw('presupuesto_id, SUM(subtotal) AS subtotal')
            ->groupBy('presupuesto_id');

        return DB::table('presupuestos')
            ->join('pacientes', 'pacientes.id', '=', 'presupuestos.paciente_id')
            ->join('convenios', 'convenios.id', '=', 'pacientes.convenio_id')
            ->joinSub($lineas, 'lineas', 'lineas.presupuesto_id', '=', 'presupuestos.id')
            ->whereIn('presupuestos.estado', ['aceptado', 'pagado'])
            ->whereBetween('presupuestos.created_at', [$desde, $hasta])
            ->groupBy('convenios.institucion')
            ->selectRaw('convenios.institucion, COUNT(*) AS presupuestos, SUM(lineas.subtotal - presupuestos.total_calculado) AS descuento')
            ->orderByDesc('descuento')
            ->get()
            ->map(fn ($fila) => [
                'institucion' => $fila->institucion,
                'presupuestos' => (int) $fila->presupuestos,
                'descuento' => (float) $fila->descuento,
            ])
            ->all();
    }

    private function saldosAntiguos(): array
    {
        $lista = Presupuesto::with('paciente')
            ->where('estado', 'aceptado')
            ->where('created_at', '<', now()->subDays(self::DIAS_SALDO_ANTIGUO))
            ->withSum(['pagos as pagado' => fn ($q) => $q->where('estado', 'vigente')], 'monto')
            ->orderBy('created_at')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'paciente' => $p->paciente->nombre,
                'dias' => (int) $p->created_at->diffInDays(now()),
                'saldo' => (float) $p->total_calculado - (float) $p->pagado,
            ])
            ->filter(fn ($fila) => $fila['saldo'] > 0)
            ->values();

        return [
            'cantidad' => $lista->count(),
            'monto' => (float) $lista->sum('saldo'),
            'lista' => $lista->take(5)->all(),
        ];
    }

    private function pacientesPorConvenio(): array
    {
        return DB::table('pacientes')
            ->leftJoin('convenios', 'convenios.id', '=', 'pacientes.convenio_id')
            ->where('pacientes.activo', true)
            ->groupBy('convenios.institucion')
            ->selectRaw("COALESCE(convenios.institucion, 'Particular') AS categoria, COUNT(*) AS total")
            ->orderByDesc('total')
            ->get()
            ->map(fn ($fila) => ['categoria' => $fila->categoria, 'total' => (int) $fila->total])
            ->all();
    }

    private function pacientesDelMes(Carbon $desde, Carbon $hasta): array
    {
        return [
            'nuevos' => Paciente::whereBetween('created_at', [$desde, $hasta])->count(),
            'atendidos' => Cita::where('estado', 'atendida')
                ->whereBetween('hora_inicio', [$desde, $hasta])
                ->distinct()
                ->count('paciente_id'),
            'activos' => Paciente::where('activo', true)->count(),
        ];
    }

    private function tratamientosMasSolicitados(Carbon $desde, Carbon $hasta): array
    {
        return DB::table('detalle_presupuestos')
            ->join('presupuestos', 'presupuestos.id', '=', 'detalle_presupuestos.presupuesto_id')
            ->join('tratamientos', 'tratamientos.id', '=', 'detalle_presupuestos.tratamiento_id')
            ->whereIn('presupuestos.estado', ['aceptado', 'pagado'])
            ->whereBetween('presupuestos.created_at', [$desde, $hasta])
            ->groupBy('tratamientos.nombre')
            ->selectRaw('tratamientos.nombre, SUM(detalle_presupuestos.cantidad) AS cantidad, SUM(detalle_presupuestos.subtotal) AS monto')
            ->orderByDesc('cantidad')
            ->orderByDesc('monto')
            ->limit(5)
            ->get()
            ->map(fn ($fila) => ['nombre' => $fila->nombre, 'cantidad' => (int) $fila->cantidad, 'monto' => (float) $fila->monto])
            ->all();
    }

    private function ocupacionPorBox(Carbon $desde, Carbon $hasta): array
    {
        $diasHabiles = 0;
        for ($dia = $desde->copy(); $dia->lte($hasta); $dia->addDay()) {
            if (! $dia->isWeekend()) {
                $diasHabiles++;
            }
        }

        $minutosDisponibles = $diasHabiles * $this->minutosDeAtencionPorDia();

        $minutosUsados = DB::table('citas')
            ->whereNotIn('estado', Cita::ESTADOS_LIBERAN_HORARIO)
            ->whereBetween('hora_inicio', [$desde, $hasta])
            ->groupBy('box_id')
            ->selectRaw('box_id, SUM(EXTRACT(EPOCH FROM (hora_fin - hora_inicio)) / 60) AS minutos')
            ->pluck('minutos', 'box_id');

        return Box::with('sucursal')
            ->where('activo', true)
            ->orderBy('sucursal_id')
            ->orderBy('nombre')
            ->get()
            ->map(function ($box) use ($minutosUsados, $minutosDisponibles) {
                $minutos = (float) ($minutosUsados[$box->id] ?? 0);

                return [
                    'box' => $box->nombre,
                    'sucursal' => $box->sucursal->nombre,
                    'horas' => round($minutos / 60, 1),
                    'porcentaje' => $minutosDisponibles > 0 ? (int) round($minutos / $minutosDisponibles * 100) : 0,
                ];
            })
            ->all();
    }

    private function cancelacion(Carbon $desde, Carbon $hasta): array
    {
        $total = Cita::whereBetween('hora_inicio', [$desde, $hasta])->count();
        $canceladas = Cita::whereBetween('hora_inicio', [$desde, $hasta])->where('estado', 'cancelada')->count();
        $inasistencias = Cita::whereBetween('hora_inicio', [$desde, $hasta])->where('estado', 'no_asistio')->count();

        return [
            'total' => $total,
            'canceladas' => $canceladas,
            'inasistencias' => $inasistencias,
            'tasa' => $total > 0 ? round($canceladas / $total * 100, 1) : 0,
        ];
    }

    private function demanda(Carbon $desde, Carbon $hasta): array
    {
        $inicio = $desde->copy()->subMonths(2);
        $horas = range((int) substr(CitaService::HORA_APERTURA, 0, 2), (int) substr(CitaService::HORA_CIERRE, 0, 2) - 1);
        $dias = array_slice(Horario::DIAS, 0, 5, true);

        $matriz = [];
        foreach (array_keys($dias) as $dia) {
            $matriz[$dia] = array_fill_keys($horas, 0);
        }

        $filas = DB::table('citas')
            ->whereNotIn('estado', Cita::ESTADOS_LIBERAN_HORARIO)
            ->whereBetween('hora_inicio', [$inicio, $hasta])
            ->selectRaw('EXTRACT(ISODOW FROM hora_inicio) AS dia, EXTRACT(HOUR FROM hora_inicio) AS hora, COUNT(*) AS total')
            ->groupBy('dia', 'hora')
            ->get();

        foreach ($filas as $fila) {
            $dia = (int) $fila->dia;
            $hora = (int) $fila->hora;
            if (isset($matriz[$dia][$hora])) {
                $matriz[$dia][$hora] = (int) $fila->total;
            }
        }

        return [
            'desde' => $inicio,
            'dias' => $dias,
            'horas' => $horas,
            'matriz' => $matriz,
            'maximo' => max(1, ...array_map(fn ($fila) => max($fila), $matriz)),
        ];
    }

    private function actividadPorSucursal(Carbon $desde, Carbon $hasta): array
    {
        return DB::table('sucursales')
            ->leftJoin('box', 'box.sucursal_id', '=', 'sucursales.id')
            ->leftJoin('citas', function ($join) use ($desde, $hasta) {
                $join->on('citas.box_id', '=', 'box.id')
                    ->whereNotIn('citas.estado', Cita::ESTADOS_LIBERAN_HORARIO)
                    ->whereBetween('citas.hora_inicio', [$desde, $hasta]);
            })
            ->groupBy('sucursales.id', 'sucursales.nombre')
            ->orderBy('sucursales.nombre')
            ->selectRaw('sucursales.nombre, COUNT(DISTINCT box.id) AS boxes, COUNT(citas.id) AS citas,
                COUNT(DISTINCT citas.paciente_id) AS pacientes,
                COALESCE(SUM(EXTRACT(EPOCH FROM (citas.hora_fin - citas.hora_inicio))) / 3600, 0) AS horas')
            ->get()
            ->map(fn ($fila) => [
                'nombre' => $fila->nombre,
                'boxes' => (int) $fila->boxes,
                'citas' => (int) $fila->citas,
                'pacientes' => (int) $fila->pacientes,
                'horas' => round((float) $fila->horas, 1),
            ])
            ->all();
    }
}
