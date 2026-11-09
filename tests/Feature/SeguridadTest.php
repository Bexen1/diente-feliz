<?php

namespace Tests\Feature;

use App\Models\AuditoriaLog;
use App\Models\Usuario;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Tests\TestCase;

class SeguridadTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_respuestas_incluyen_cabeceras_de_seguridad(): void
    {
        $respuesta = $this->get('/login')->assertOk();

        $csp = $respuesta->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringNotContainsString('unsafe-eval', $csp);

        $respuesta->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_acceso_denegado_por_policy_queda_en_la_auditoria(): void
    {
        $dentista = Usuario::where('email', 'camila@dientefeliz.test')->first();

        $this->actingAs($dentista)->get('/citas/2/reagendar')->assertForbidden();

        $log = AuditoriaLog::where('accion', 'acceso_denegado')->latest('id')->first();
        $this->assertSame('citas/2/reagendar', $log->payload_cambios_json['despues']['ruta']);
        $this->assertSame('policy', $log->payload_cambios_json['despues']['origen']);
        $this->assertSame($dentista->id, $log->usuario_id);
    }

    private function middlewareCsrfActivo(): ValidateCsrfToken
    {
        return new class(app(), app('encrypter')) extends ValidateCsrfToken
        {
            protected function runningUnitTests()
            {
                return false;
            }
        };
    }

    private function peticionConSesion(array $datos): Request
    {
        $sesion = app('session')->driver();
        $sesion->start();

        $request = Request::create('/pacientes', 'POST', $datos);
        $request->headers->set('Sec-Fetch-Site', 'cross-site');
        $request->setLaravelSession($sesion);

        return $request;
    }

    public function test_formulario_sin_token_csrf_se_rechaza_con_419(): void
    {
        $request = $this->peticionConSesion(['nombre' => 'Intento externo']);

        try {
            $this->middlewareCsrfActivo()->handle($request, fn () => response('aceptado'));
            $this->fail('La petición sin token debió ser rechazada.');
        } catch (TokenMismatchException $e) {
            $respuesta = app(ExceptionHandler::class)->render($request, $e);
            $this->assertSame(419, $respuesta->getStatusCode());
        }
    }

    public function test_formulario_con_token_csrf_valido_se_acepta(): void
    {
        $request = $this->peticionConSesion([]);
        $request->merge(['_token' => $request->session()->token()]);

        $respuesta = $this->middlewareCsrfActivo()->handle($request, fn () => response('aceptado'));

        $this->assertSame('aceptado', $respuesta->getContent());
    }
}
