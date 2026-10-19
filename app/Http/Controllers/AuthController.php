<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\Usuario;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const INTENTOS_POR_MINUTO = 5;

    private const FALLOS_PARA_BLOQUEO = 10;

    private const SEGUNDOS_BLOQUEO = 15 * 60;

    public function mostrarLogin()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        $email = Str::lower($request->validated('email'));
        $claveIntentos = 'login:'.$email.'|'.$request->ip();
        $claveBloqueo = 'bloqueo:'.$email;

        if (RateLimiter::tooManyAttempts($claveBloqueo, self::FALLOS_PARA_BLOQUEO)) {
            $minutos = (int) ceil(RateLimiter::availableIn($claveBloqueo) / 60);

            return back()->withInput(['email' => $email])
                ->withErrors(['email' => "Cuenta bloqueada temporalmente. Intente nuevamente en {$minutos} minutos."]);
        }

        if (RateLimiter::tooManyAttempts($claveIntentos, self::INTENTOS_POR_MINUTO)) {
            abort(429);
        }

        $credenciales = ['email' => $email, 'password' => $request->validated('password'), 'activo' => true];

        if (Auth::attempt($credenciales)) {
            $request->session()->regenerate();
            RateLimiter::clear($claveIntentos);
            RateLimiter::clear($claveBloqueo);
            AuditoriaService::registrar('ingreso', 'usuarios', Auth::id());

            return redirect()->intended(route('inicio'));
        }

        RateLimiter::hit($claveIntentos, 60);
        RateLimiter::hit($claveBloqueo, self::SEGUNDOS_BLOQUEO);

        $usuarioId = Usuario::where('email', $email)->value('id');
        AuditoriaService::registrar('ingreso_fallido', 'usuarios', $usuarioId, null, ['email' => $email], $usuarioId);

        if (RateLimiter::tooManyAttempts($claveBloqueo, self::FALLOS_PARA_BLOQUEO)) {
            AuditoriaService::registrar('cuenta_bloqueada', 'usuarios', $usuarioId, null, ['email' => $email], $usuarioId);
        }

        return back()->withInput(['email' => $email])
            ->withErrors(['email' => 'Correo o contraseña incorrectos.']);
    }

    public function logout(Request $request)
    {
        AuditoriaService::registrar('salida', 'usuarios', Auth::id());

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
