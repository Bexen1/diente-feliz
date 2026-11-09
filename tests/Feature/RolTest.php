<?php

namespace Tests\Feature;

use App\Models\AuditoriaLog;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function usuario(string $nombre): Usuario
    {
        return Usuario::where('email', "$nombre@dientefeliz.test")->first();
    }

    public function test_administrador_ve_roles_y_matriz_de_permisos(): void
    {
        $this->actingAs($this->usuario('admin'))->get('/roles')
            ->assertOk()
            ->assertSee('Matriz de permisos')
            ->assertSee('Antecedentes de salud')
            ->assertSee('Solo la propia')
            ->assertSee('Atiende la recepción');
    }

    public function test_edita_la_descripcion_pero_no_el_nombre(): void
    {
        $rol = Rol::where('nombre', Rol::RECEPCIONISTA)->first();

        $this->actingAs($this->usuario('admin'))->put("/roles/{$rol->id}", [
            'nombre' => 'Otro nombre',
            'descripcion' => 'Recepción y caja de la clínica.',
        ])->assertRedirect('/roles');

        $rol->refresh();
        $this->assertSame(Rol::RECEPCIONISTA, $rol->nombre);
        $this->assertSame('Recepción y caja de la clínica.', $rol->descripcion);
        $this->assertTrue(AuditoriaLog::where('tabla_afectada', 'roles')->where('accion', 'actualizar')->exists());
    }

    public function test_otros_roles_no_acceden(): void
    {
        $this->actingAs($this->usuario('valentina'))->get('/roles')->assertForbidden();
        $this->actingAs($this->usuario('camila'))->get('/roles/1/edit')->assertForbidden();
    }
}
