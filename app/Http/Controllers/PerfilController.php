<?php

namespace App\Http\Controllers;

use App\Http\Requests\CambiarContrasenaRequest;
use App\Http\Requests\TemaRequest;
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
        $eraObligatorio = $usuario->debe_cambiar_password;
        $usuario->update(['password' => $request->validated('password'), 'debe_cambiar_password' => false]);

        AuditoriaService::registrar('cambiar_contrasena', 'usuarios', $usuario->id);

        return redirect()->route($eraObligatorio ? 'inicio' : 'perfil.edit')->with('exito', 'Contraseña actualizada.');
    }

    public function tema(TemaRequest $request)
    {
        $request->user()->update(['tema' => $request->validated('tema')]);

        return back();
    }
}
