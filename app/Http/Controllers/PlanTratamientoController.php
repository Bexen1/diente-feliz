<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrarRealizadoRequest;
use App\Models\DetallePresupuesto;
use App\Services\PlanTratamientoService;

class PlanTratamientoController extends Controller
{
    public function show(DetallePresupuesto $detalle)
    {
        $detalle->load([
            'tratamiento',
            'presupuesto.paciente.convenio',
            'presupuesto.doctor.usuario',
            'presupuesto.detalles.tratamiento',
            'presupuesto.pagos' => fn ($q) => $q->orderBy('fecha')->orderBy('id'),
            'sesiones' => fn ($q) => $q->with('doctor.usuario')->orderBy('fecha')->orderBy('id'),
        ]);

        $presupuesto = $detalle->presupuesto;
        $pagado = (float) $presupuesto->pagos->where('estado', 'vigente')->sum('monto');

        return view('fichas.tratamiento', [
            'detalle' => $detalle,
            'presupuesto' => $presupuesto,
            'paciente' => $presupuesto->paciente,
            'pagado' => $pagado,
            'saldo' => max(0, (float) $presupuesto->total_calculado - $pagado),
            'avance' => $detalle->cantidad > 0 ? (int) round($detalle->cantidad_realizada / $detalle->cantidad * 100) : 0,
            'puedeRegistrar' => auth()->user()->puede('plan.registrar') && in_array($presupuesto->estado, PlanTratamientoService::ESTADOS_CON_PLAN, true) && $detalle->pendientes() > 0,
        ]);
    }

    public function registrar(RegistrarRealizadoRequest $request, DetallePresupuesto $detalle, PlanTratamientoService $plan)
    {
        $plan->registrarRealizado($detalle, $request->user());

        $destino = $request->validated('volver') === 'detalle'
            ? route('detalles.show', $detalle)
            : route('fichas.show', $detalle->presupuesto->paciente_id);

        return redirect($destino)->with('exito', $detalle->tratamiento->nombre.' registrado como realizado.');
    }
}
