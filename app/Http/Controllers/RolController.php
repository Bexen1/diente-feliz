<?php

namespace App\Http\Controllers;

use App\Http\Requests\PermisosRequest;
use App\Http\Requests\RolRequest;
use App\Models\Permiso;
use App\Models\Rol;
use App\Services\AuditoriaService;
use Illuminate\Support\Facades\DB;

class RolController extends Controller
{
    public function index()
    {
        return view('roles.index', [
            'roles' => Rol::with('permisos')->withCount('usuarios')->orderBy('id')->get(),
            'grupos' => Permiso::orderBy('orden')->get()->groupBy('grupo'),
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

    public function actualizarPermisos(PermisosRequest $request)
    {
        $seleccion = $request->validated('permisos') ?? [];
        $permisos = Permiso::pluck('id', 'clave');
        $cambios = 0;

        DB::transaction(function () use ($seleccion, $permisos, &$cambios) {
            foreach (Rol::with('permisos')->where('nombre', '<>', Rol::ADMINISTRADOR)->get() as $rol) {
                $antes = $rol->clavesPermisos()->sort()->values()->all();
                $despues = collect($seleccion[$rol->id] ?? [])->unique()->sort()->values()->all();

                if ($antes === $despues) {
                    continue;
                }

                $rol->permisos()->sync($permisos->only($despues)->values()->all());
                AuditoriaService::registrar('actualizar_permisos', 'roles', $rol->id,
                    ['permisos' => $antes], ['permisos' => $despues]);
                $cambios++;
            }
        });

        return redirect()->route('roles.index')->with('exito', $cambios === 0
            ? 'No hubo cambios en los permisos.'
            : 'Permisos actualizados para '.$cambios.' '.($cambios === 1 ? 'rol' : 'roles').'.');
    }
}
