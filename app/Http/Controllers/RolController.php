<?php

namespace App\Http\Controllers;

use App\Http\Requests\RolRequest;
use App\Models\Rol;
use App\Services\AuditoriaService;

class RolController extends Controller
{
    public function index()
    {
        return view('roles.index', [
            'roles' => Rol::withCount('usuarios')->orderBy('id')->get(),
            'matriz' => Rol::MATRIZ_PERMISOS,
        ]);
    }

    public function edit(Rol $rol)
    {
        return view('roles.form', ['rol' => $rol]);
    }

    public function update(RolRequest $request, Rol $rol)
    {
        $original = $rol->getOriginal();
        $rol->update($request->validated());
        AuditoriaService::actualizado($rol, $original);

        return redirect()->route('roles.index')->with('exito', 'Descripción del rol actualizada.');
    }
}
