<?php

namespace App\Http\Controllers;

use App\Models\DetallePresupuesto;
use App\Services\PlanTratamientoService;
use Illuminate\Http\Request;

class PlanTratamientoController extends Controller
{
    public function registrar(Request $request, DetallePresupuesto $detalle, PlanTratamientoService $plan)
    {
        $plan->registrarRealizado($detalle, $request->user());

        $paciente = $detalle->presupuesto->paciente_id;

        return redirect()->route('fichas.show', $paciente)
            ->with('exito', $detalle->tratamiento->nombre.' registrado como realizado.');
    }
}
