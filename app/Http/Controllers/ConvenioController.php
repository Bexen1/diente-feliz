<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmacionRequest;
use App\Http\Requests\ConvenioRequest;
use App\Models\Convenio;
use App\Services\AuditoriaService;

class ConvenioController extends Controller
{
    public function index()
    {
        return view('convenios.index', [
            'convenios' => Convenio::withCount('pacientes')->orderBy('institucion')->get(),
        ]);
    }

    public function create()
    {
        return view('convenios.form', ['convenio' => new Convenio]);
    }

    public function store(ConvenioRequest $request)
    {
        $convenio = Convenio::create($request->validated());
        AuditoriaService::creado($convenio);

        return redirect()->route('convenios.index')->with('exito', 'Convenio creado.');
    }

    public function edit(Convenio $convenio)
    {
        return view('convenios.form', compact('convenio'));
    }

    public function update(ConvenioRequest $request, Convenio $convenio)
    {
        $original = $convenio->getOriginal();
        $convenio->update($request->validated());
        AuditoriaService::actualizado($convenio, $original);

        return redirect()->route('convenios.index')->with('exito', 'Convenio actualizado.');
    }

    public function confirmarEliminacion(Convenio $convenio)
    {
        return view('confirmar', [
            'titulo' => 'Eliminar convenio',
            'mensaje' => 'El convenio se eliminará definitivamente. Solo es posible si ningún paciente lo tiene asignado.',
            'detalles' => [
                'Institución' => $convenio->institucion,
                'Descuento' => rtrim(rtrim($convenio->porcentaje_descuento, '0'), '.').' %',
            ],
            'accion' => route('convenios.destroy', $convenio),
            'metodo' => 'DELETE',
            'boton' => 'Sí, eliminar el convenio',
            'volver' => route('convenios.index'),
        ]);
    }

    public function destroy(ConfirmacionRequest $request, Convenio $convenio)
    {
        if ($convenio->pacientes()->exists()) {
            return redirect()->route('convenios.index')->with('error', 'No se puede eliminar: hay pacientes con este convenio.');
        }

        $antes = $convenio->getAttributes();
        $convenio->delete();
        AuditoriaService::registrar('eliminar', 'convenios', $convenio->id, $antes, ['motivo' => $request->validated('motivo')]);

        return redirect()->route('convenios.index')->with('exito', 'Convenio eliminado.');
    }
}
