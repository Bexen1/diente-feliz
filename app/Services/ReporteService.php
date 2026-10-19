<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReporteService
{
    public const TIPOS = [
        'doctores' => 'Citas por doctor',
        'box' => 'Ocupación por box',
        'sucursales' => 'Actividad por sucursal',
        'pacientes' => 'Pacientes (clientes)',
        'ventas' => 'Historial de ventas (pagos)',
    ];

    public function generar(string $tipo, Carbon $desde, Carbon $hasta): array
    {
        $hasta = $hasta->copy()->endOfDay();

        [$columnas, $filas] = match ($tipo) {
            'doctores' => $this->porDoctor($desde, $hasta),
            'box' => $this->porBox($desde, $hasta),
            'sucursales' => $this->porSucursal($desde, $hasta),
            'pacientes' => $this->pacientes($desde, $hasta),
            'ventas' => $this->ventas($desde, $hasta),
        };

        return ['titulo' => self::TIPOS[$tipo], 'columnas' => $columnas, 'filas' => $filas];
    }

    private function porDoctor(Carbon $desde, Carbon $hasta): array
    {
        $filas = DB::table('doctores')
            ->join('usuarios', 'usuarios.id', '=', 'doctores.usuario_id')
            ->leftJoin('citas', function ($join) use ($desde, $hasta) {
                $join->on('citas.doctor_id', '=', 'doctores.id')->whereBetween('citas.hora_inicio', [$desde, $hasta]);
            })
            ->groupBy('usuarios.nombre', 'doctores.especialidad')
            ->orderBy('usuarios.nombre')
            ->selectRaw("usuarios.nombre, doctores.especialidad, COUNT(citas.id) AS total,
                SUM(CASE WHEN citas.estado = 'atendida' THEN 1 ELSE 0 END) AS atendidas,
                SUM(CASE WHEN citas.estado = 'cancelada' THEN 1 ELSE 0 END) AS canceladas")
            ->get()
            ->map(fn ($f) => [$f->nombre, $f->especialidad, (int) $f->total, (int) $f->atendidas, (int) $f->canceladas])
            ->all();

        return [['Doctor', 'Especialidad', 'Citas', 'Atendidas', 'Canceladas'], $filas];
    }

    private function porBox(Carbon $desde, Carbon $hasta): array
    {
        $filas = DB::table('box')
            ->join('sucursales', 'sucursales.id', '=', 'box.sucursal_id')
            ->leftJoin('citas', function ($join) use ($desde, $hasta) {
                $join->on('citas.box_id', '=', 'box.id')
                    ->where('citas.estado', '<>', 'cancelada')
                    ->whereBetween('citas.hora_inicio', [$desde, $hasta]);
            })
            ->groupBy('sucursales.nombre', 'box.nombre')
            ->orderBy('sucursales.nombre')->orderBy('box.nombre')
            ->selectRaw('sucursales.nombre AS sucursal, box.nombre AS box, COUNT(citas.id) AS citas,
                COALESCE(SUM(EXTRACT(EPOCH FROM (citas.hora_fin - citas.hora_inicio)) / 3600), 0) AS horas')
            ->get()
            ->map(fn ($f) => [$f->sucursal, $f->box, (int) $f->citas, round((float) $f->horas, 1)])
            ->all();

        return [['Sucursal', 'Box', 'Citas (sin canceladas)', 'Horas ocupadas'], $filas];
    }

    private function porSucursal(Carbon $desde, Carbon $hasta): array
    {
        $citas = DB::table('citas')
            ->join('box', 'box.id', '=', 'citas.box_id')
            ->where('citas.estado', '<>', 'cancelada')
            ->whereBetween('citas.hora_inicio', [$desde, $hasta])
            ->groupBy('box.sucursal_id')
            ->selectRaw('box.sucursal_id, COUNT(*) AS citas, COUNT(DISTINCT citas.paciente_id) AS pacientes')
            ->get()->keyBy('sucursal_id');

        $filas = DB::table('sucursales')->orderBy('nombre')->get()
            ->map(fn ($s) => [
                $s->nombre,
                $s->direccion,
                (int) ($citas[$s->id]->citas ?? 0),
                (int) ($citas[$s->id]->pacientes ?? 0),
            ])->all();

        return [['Sucursal', 'Dirección', 'Citas', 'Pacientes atendidos'], $filas];
    }

    private function pacientes(Carbon $desde, Carbon $hasta): array
    {
        $citas = DB::table('citas')->whereBetween('hora_inicio', [$desde, $hasta])
            ->groupBy('paciente_id')->selectRaw('paciente_id, COUNT(*) AS total')->pluck('total', 'paciente_id');

        $pagado = DB::table('pagos')
            ->join('presupuestos', 'presupuestos.id', '=', 'pagos.presupuesto_id')
            ->where('pagos.estado', 'vigente')
            ->whereBetween('pagos.fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->groupBy('presupuestos.paciente_id')
            ->selectRaw('presupuestos.paciente_id, SUM(pagos.monto) AS total')
            ->pluck('total', 'paciente_id');

        $filas = DB::table('pacientes')
            ->leftJoin('convenios', 'convenios.id', '=', 'pacientes.convenio_id')
            ->orderBy('pacientes.nombre')
            ->get(['pacientes.id', 'pacientes.rut', 'pacientes.nombre', 'pacientes.telefono', 'convenios.institucion'])
            ->map(fn ($p) => [
                $p->rut, $p->nombre, $p->telefono, $p->institucion ?? 'Particular',
                (int) ($citas[$p->id] ?? 0), (float) ($pagado[$p->id] ?? 0),
            ])->all();

        return [['RUT', 'Nombre', 'Teléfono', 'Convenio', 'Citas en el período', 'Pagado en el período'], $filas];
    }

    private function ventas(Carbon $desde, Carbon $hasta): array
    {
        $filas = DB::table('pagos')
            ->join('presupuestos', 'presupuestos.id', '=', 'pagos.presupuesto_id')
            ->join('pacientes', 'pacientes.id', '=', 'presupuestos.paciente_id')
            ->join('doctores', 'doctores.id', '=', 'presupuestos.doctor_id')
            ->join('usuarios', 'usuarios.id', '=', 'doctores.usuario_id')
            ->whereBetween('pagos.fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->orderBy('pagos.fecha')->orderBy('pagos.id')
            ->get(['pagos.fecha', 'presupuestos.id AS presupuesto', 'pacientes.rut', 'pacientes.nombre AS paciente',
                'usuarios.nombre AS doctor', 'pagos.monto', 'pagos.estado'])
            ->map(fn ($v) => [
                Carbon::parse($v->fecha)->format('d/m/Y'), $v->presupuesto, $v->rut, $v->paciente,
                $v->doctor, (float) $v->monto, ucfirst($v->estado),
            ])->all();

        return [['Fecha', 'Presupuesto N.°', 'RUT', 'Paciente', 'Doctor', 'Monto', 'Estado'], $filas];
    }
}
