<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CambioContrasenaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function usuario(string $nombre): Usuario
    {
        return Usuario::where('email', "$nombre@dientefeliz.test")->first();
    }

    public function test_usuario_nuevo_debe_cambiar_su_contrasena(): void
    {
        $this->actingAs($this->usuario('admin'))->post('/usuarios', [
            'nombre' => 'Nuevo Recepcionista', 'email' => 'nuevo@dientefeliz.test', 'rol_id' => 3, 'activo' => 1,
            'password' => 'ClaveTemporal#2026', 'password_confirmation' => 'ClaveTemporal#2026',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Usuario::where('email', 'nuevo@dientefeliz.test')->first()->debe_cambiar_password);
    }

    public function test_restablecer_contrasena_obliga_a_cambiarla_y_bloquea_la_navegacion(): void
    {
        $recepcionista = $this->usuario('valentina');

        $this->actingAs($this->usuario('admin'))->put("/usuarios/{$recepcionista->id}", [
            'nombre' => $recepcionista->nombre, 'email' => $recepcionista->email, 'rol_id' => $recepcionista->rol_id, 'activo' => 1,
            'password' => 'Restablecida#2026', 'password_confirmation' => 'Restablecida#2026',
        ])->assertSessionHasNoErrors();

        $recepcionista->refresh();
        $this->assertTrue($recepcionista->debe_cambiar_password);

        $this->actingAs($recepcionista)->get('/agenda')
            ->assertRedirect('/perfil')
            ->assertSessionHas('error', 'Debe cambiar su contraseña antes de continuar.');
        $this->actingAs($recepcionista)->get('/perfil')->assertOk();

        $this->actingAs($recepcionista)->put('/perfil', [
            'password_actual' => 'Restablecida#2026',
            'password' => 'MiClavePropia#2026',
            'password_confirmation' => 'MiClavePropia#2026',
        ])->assertRedirect('/');

        $this->assertFalse($recepcionista->fresh()->debe_cambiar_password);
        $this->actingAs($recepcionista->fresh())->get('/agenda')->assertOk();
    }

    public function test_el_administrador_no_se_obliga_a_si_mismo(): void
    {
        $admin = $this->usuario('admin');

        $this->actingAs($admin)->put("/usuarios/{$admin->id}", [
            'nombre' => $admin->nombre, 'email' => $admin->email, 'rol_id' => $admin->rol_id, 'activo' => 1,
            'password' => 'NuevaClaveAdmin#2026', 'password_confirmation' => 'NuevaClaveAdmin#2026',
        ]);

        $this->assertFalse($admin->fresh()->debe_cambiar_password);
    }

    public function test_el_campo_no_se_puede_enviar_desde_el_formulario(): void
    {
        $recepcionista = $this->usuario('valentina');

        $this->actingAs($this->usuario('admin'))->put("/usuarios/{$recepcionista->id}", [
            'nombre' => $recepcionista->nombre, 'email' => $recepcionista->email, 'rol_id' => $recepcionista->rol_id, 'activo' => 1,
            'debe_cambiar_password' => 1,
        ]);

        $this->assertFalse($recepcionista->fresh()->debe_cambiar_password);
    }
}
