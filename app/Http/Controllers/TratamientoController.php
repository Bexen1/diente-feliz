<?php

namespace App\Http\Controllers;

use App\Http\Requests\TratamientoRequest;
use App\Models\Tratamiento;
use App\Services\AuditoriaService;

class TratamientoController extends Controller
{
    public function index()
    {
        return view('tratamientos.index', ['tratamientos' => Tratamiento::orderBy('nombre')->get()]);
    }

    public function create()
    {
        return view('tratamientos.form', ['tratamiento' => new Tratamiento]);
    }

    public function store(TratamientoRequest $request)
    {
        $tratamiento = Tratamiento::create($request->validated());
        AuditoriaService::creado($tratamiento);

        return redirect()->route('tratamientos.index')->with('exito', 'Tratamiento creado.');
    }

    public function edit(Tratamiento $tratamiento)
    {
        return view('tratamientos.form', compact('tratamiento'));
    }

    public function update(TratamientoRequest $request, Tratamiento $tratamiento)
    {
        $original = $tratamiento->getOriginal();
        $tratamiento->update($request->validated());
        AuditoriaService::actualizado($tratamiento, $original);

        return redirect()->route('tratamientos.index')->with('exito', 'Tratamiento actualizado.');
    }
}
