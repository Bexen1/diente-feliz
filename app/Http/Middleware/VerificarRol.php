<?php

namespace App\Http\Middleware;

use App\Services\AuditoriaService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        if (! $usuario->activo) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['email' => 'Su cuenta está desactivada.']);
        }

        if ($usuario->debe_cambiar_password && ! $request->routeIs('perfil.edit', 'perfil.update', 'logout')) {
            return redirect()->route('perfil.edit')->with('error', 'Debe cambiar su contraseña antes de continuar.');
        }

        if ($roles !== [] && ! $usuario->tieneRol(...$roles)) {
            AuditoriaService::registrar('acceso_denegado', 'rutas', null, null, [
                'ruta' => $request->path(),
                'rol' => $usuario->rol->nombre,
            ]);

            abort(403);
        }

        return $next($request);
    }
}
