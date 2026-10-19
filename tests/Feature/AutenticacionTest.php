<?php

namespace Tests\Feature;

use App\Models\AuditoriaLog;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_cada_rol_puede_ingresar(): void
    {
        foreach (['admin', 'camila', 'valentina'] as $nombre) {
            $this->post('/login', ['email' => "$nombre@dientefeliz.test", 'password' => 'DienteFeliz2026'])
                ->assertRedirect(route('inicio'));

            $this->assertAuthenticated();
            $this->get('/')->assertOk();
            $this->post('/logout');
        }
    }

    public function test_credenciales_invalidas_muestran_mensaje_generico_y_se_auditan(): void
    {
        $this->from('/login')
            ->post('/login', ['email' => 'admin@dientefeliz.test', 'password' => 'incorrecta'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Correo o contraseña incorrectos.']);

        $this->assertGuest();
        $this->assertTrue(AuditoriaLog::where('accion', 'ingreso_fallido')->exists());
    }

    public function test_sexto_intento_en_un_minuto_responde_429(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->post('/login', ['email' => 'admin@dientefeliz.test', 'password' => 'incorrecta']);
        }

        $this->post('/login', ['email' => 'admin@dientefeliz.test', 'password' => 'incorrecta'])
            ->assertStatus(429);
    }

    public function test_cuenta_desactivada_no_puede_ingresar(): void
    {
        Usuario::where('email', 'valentina@dientefeliz.test')->update(['activo' => false]);

        $this->post('/login', ['email' => 'valentina@dientefeliz.test', 'password' => 'DienteFeliz2026']);

        $this->assertGuest();
    }

    public function test_ruta_inexistente_muestra_pagina_generica(): void
    {
        $this->actingAs(Usuario::first())
            ->get('/no-existe')
            ->assertNotFound()
            ->assertSee('Página no encontrada');
    }
}
