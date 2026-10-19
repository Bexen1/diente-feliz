<?php

namespace App\Http\Controllers;

use App\Http\Requests\CambiarContrasenaRequest;
use App\Services\AuditoriaService;

class PerfilController extends Controller
{
    public function edit()
    {
        return view('perfil.edit');
    }

    public function update(CambiarContrasenaRequest $request)
    {
        $usuario = $request->user();
        $usuario->update(['password' => $request->validated('password')]);

        AuditoriaService::registrar('cambiar_contrasena', 'usuarios', $usuario->id);

        return redirect()->route('perfil.edit')->with('exito', 'Contraseña actualizada.');
    }
}
