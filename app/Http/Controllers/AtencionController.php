<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Evolucion;
use App\Services\OdontogramaService;

class AtencionController extends Controller
{
    public function show(Evolucion $evolucion, OdontogramaService $odontograma)
    {
        $evolucion->load(['doctor.usuario', 'ficha.paciente.convenio', 'detalle.tratamiento', 'detalle.presupuesto']);
        $ficha = $evolucion->ficha;
        $dia = $evolucion->fecha->toDateString();

        $anterior = $ficha->evoluciones()
            ->where(fn ($q) => $q->where('fecha', '<', $dia)->orWhere(fn ($q) => $q->where('fecha', $dia)->where('id', '<', $evolucion->id)))
            ->orderByDesc('fecha')->orderByDesc('id')->first();
        $siguiente = $ficha->evoluciones()
            ->where(fn ($q) => $q->where('fecha', '>', $dia)->orWhere(fn ($q) => $q->where('fecha', $dia)->where('id', '>', $evolucion->id)))
            ->orderBy('fecha')->orderBy('id')->first();

        return view('fichas.atencion', [
            'evolucion' => $evolucion,
            'paciente' => $ficha->paciente,
            'citas' => Cita::with(['doctor.usuario', 'box.sucursal'])
                ->where('paciente_id', $ficha->paciente_id)
                ->whereDate('hora_inicio', $dia)
                ->orderBy('hora_inicio')
                ->get(),
            'cambios' => $odontograma->historial($ficha, null, 50, $dia),
            'radiografias' => $ficha->radiografias()->whereDate('fecha', $dia)->get(),
            'otras' => $ficha->evoluciones()->with('doctor.usuario')->whereDate('fecha', $dia)->where('id', '<>', $evolucion->id)->get(),
            'anterior' => $anterior,
            'siguiente' => $siguiente,
        ]);
    }
}
