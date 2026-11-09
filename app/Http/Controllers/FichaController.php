<?php

namespace App\Http\Controllers;

use App\Http\Requests\EvolucionRequest;
use App\Http\Requests\FichaRequest;
use App\Http\Requests\FichaVerRequest;
use App\Http\Requests\HallazgoRequest;
use App\Models\Doctor;
use App\Models\Evolucion;
use App\Models\Paciente;
use App\Services\AuditoriaService;
use App\Services\OdontogramaService;
use App\Services\PlanTratamientoService;

class FichaController extends Controller
{
    public function show(FichaVerRequest $request, Paciente $paciente, PlanTratamientoService $plan, OdontogramaService $odontograma)
    {
        $piezaFiltro = $request->validated('pieza') ? (int) $request->validated('pieza') : null;

        $ficha = $paciente->ficha()->with([
            'evoluciones' => fn ($q) => $q->with('doctor.usuario')->orderByDesc('fecha')->orderByDesc('id'),
            'radiografias' => fn ($q) => $q->orderByDesc('fecha'),
        ])->firstOrFail();

        AuditoriaService::registrar('ver', 'fichas_clinicas', $ficha->id);

        return view('fichas.show', [
            'paciente' => $paciente->load('convenio'),
            'ficha' => $ficha,
            'odontograma' => $ficha->estado_odontograma_json ?? [],
            'doctores' => auth()->user()->doctor ? collect() : Doctor::with('usuario')->where('activo', true)->get(),
            'plan' => $plan->planDelPaciente($paciente),
            'historial' => $odontograma->historial($ficha, $piezaFiltro),
            'piezaFiltro' => $piezaFiltro,
        ]);
    }

    public function update(FichaRequest $request, Paciente $paciente)
    {
        $ficha = $paciente->ficha;
        $original = $ficha->getOriginal();
        $ficha->update($request->validated());
        AuditoriaService::actualizado($ficha, $original);

        return redirect()->route('fichas.show', $paciente)->with('exito', 'Observaciones generales guardadas.');
    }

    public function guardarHallazgo(HallazgoRequest $request, Paciente $paciente, OdontogramaService $odontograma)
    {
        $odontograma->registrarHallazgo(
            $paciente->ficha,
            (int) $request->validated('pieza'),
            $request->validated('estado'),
            $request->validated('caras') ?? [],
        );

        return redirect()->route('fichas.show', $paciente)
            ->with('exito', 'Hallazgo de la pieza '.$request->validated('pieza').' guardado.');
    }

    public function agregarEvolucion(EvolucionRequest $request, Paciente $paciente)
    {
        $datos = $request->validated();

        $evolucion = Evolucion::create([
            'ficha_id' => $paciente->ficha->id,
            'doctor_id' => $request->user()->doctor?->id ?? $datos['doctor_id'],
            'fecha' => $datos['fecha'],
            'diagnostico' => $datos['diagnostico'],
            'tratamiento_realizado' => $datos['tratamiento_realizado'] ?? null,
        ]);
        AuditoriaService::creado($evolucion);

        return redirect()->route('fichas.show', $paciente)->with('exito', 'Atención registrada en la ficha.');
    }
}
