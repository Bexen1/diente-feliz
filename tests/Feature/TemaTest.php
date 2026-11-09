<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function usuario(): Usuario
    {
        return Usuario::where('email', 'valentina@dientefeliz.test')->first();
    }

    public function test_por_defecto_el_tema_es_automatico(): void
    {
        $this->actingAs($this->usuario())->get('/')
            ->assertSee('<html lang="es">', false)
            ->assertSee('name="tema" value="oscuro"', false);
    }

    public function test_el_usuario_elige_tema_oscuro_y_se_recuerda(): void
    {
        $usuario = $this->usuario();

        $this->actingAs($usuario)->from('/pacientes')->put('/perfil/tema', ['tema' => 'oscuro'])
            ->assertRedirect('/pacientes');

        $this->assertSame('oscuro', $usuario->fresh()->tema);
        $this->actingAs($usuario->fresh())->get('/')
            ->assertSee('<html lang="es" class="oscuro">', false)
            ->assertSee('name="tema" value="claro"', false);
    }

    public function test_el_tema_claro_ignora_el_modo_del_sistema(): void
    {
        $usuario = $this->usuario();
        $usuario->update(['tema' => 'claro']);

        $this->actingAs($usuario)->get('/')
            ->assertSee('<html lang="es" class="claro">', false)
            ->assertSee('name="tema" value="auto"', false);
    }

    public function test_rechaza_un_tema_invalido(): void
    {
        $usuario = $this->usuario();

        $this->actingAs($usuario)->put('/perfil/tema', ['tema' => 'rosado'])
            ->assertSessionHasErrors('tema');

        $this->assertSame('auto', $usuario->fresh()->tema);
    }

    public function test_se_puede_cambiar_aunque_deba_cambiar_la_contrasena(): void
    {
        $usuario = $this->usuario();
        $usuario->update(['debe_cambiar_password' => true]);

        $this->actingAs($usuario)->from('/perfil')->put('/perfil/tema', ['tema' => 'oscuro'])
            ->assertRedirect('/perfil');

        $this->assertSame('oscuro', $usuario->fresh()->tema);
    }
}
