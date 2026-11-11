<?php

namespace App\Http\Middleware;

use App\Services\AuditoriaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarPermiso
{
    public function handle(Request $request, Closure $next, string ...$permisos): Response
    {
        $usuario = $request->user();

        if (! $usuario->puede(...$permisos)) {
            AuditoriaService::registrar('acceso_denegado', 'rutas', null, null, [
                'ruta' => $request->path(),
                'rol' => $usuario->rol->nombre,
                'permiso' => implode(', ', $permisos),
            ]);

            abort(403);
        }

        return $next($request);
    }
}
