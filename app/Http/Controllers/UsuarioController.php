<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsuarioRequest;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\AuditoriaService;

class UsuarioController extends Controller
{
    public function index()
    {
        return view('usuarios.index', [
            'usuarios' => Usuario::with('rol')->orderBy('nombre')->get(),
            'roles' => Rol::withCount('usuarios')->orderBy('id')->get(),
        ]);
    }

    public function create()
    {
        return view('usuarios.form', ['usuario' => new Usuario, 'roles' => Rol::orderBy('id')->get()]);
    }

    public function store(UsuarioRequest $request)
    {
        $usuario = Usuario::create($request->validated());
        AuditoriaService::creado($usuario);

        $mensaje = 'Usuario creado.';
        if ($usuario->rol->nombre === Rol::DENTISTA) {
            $mensaje .= ' Recuerde crear su perfil en "Doctores y horarios".';
        }

        return redirect()->route('usuarios.index')->with('exito', $mensaje);
    }

    public function edit(Usuario $usuario)
    {
        return view('usuarios.form', ['usuario' => $usuario, 'roles' => Rol::orderBy('id')->get()]);
    }

    public function update(UsuarioRequest $request, Usuario $usuario)
    {
        $datos = $request->validated();

        if (empty($datos['password'])) {
            unset($datos['password']);
        }

        if ($usuario->is(auth()->user())) {
            unset($datos['activo'], $datos['rol_id']);
        }

        $original = $usuario->getOriginal();
        $usuario->update($datos);
        AuditoriaService::actualizado($usuario, $original);

        if (isset($datos['password'])) {
            AuditoriaService::registrar('restablecer_contrasena', 'usuarios', $usuario->id);
        }

        return redirect()->route('usuarios.index')->with('exito', 'Usuario actualizado.');
    }
}
