<?php

use App\Http\Middleware\CabecerasSeguridad;
use App\Http\Middleware\VerificarRol;
use App\Services\AuditoriaService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['rol' => VerificarRol::class]);
        $middleware->web(append: [CabecerasSeguridad::class]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('inicio'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->user() && $e->getPrevious() instanceof AuthorizationException) {
                AuditoriaService::registrar('acceso_denegado', 'rutas', null, null, [
                    'ruta' => $request->path(),
                    'metodo' => $request->method(),
                    'rol' => $request->user()->rol->nombre,
                    'origen' => 'policy',
                ]);
            }

            return null;
        });
    })->create();
