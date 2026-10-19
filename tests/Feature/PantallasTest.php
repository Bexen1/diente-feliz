<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PantallasTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_todas_las_pantallas_abren_para_el_administrador(): void
    {
        $admin = Usuario::where('email', 'admin@dientefeliz.test')->first();
        $dia = Cita::find(1)->hora_inicio->toDateString();

        $rutas = [
            '/', '/perfil', '/agenda', "/agenda?fecha={$dia}", '/citas/1/reagendar',
            '/pacientes', '/pacientes?buscar=12.345', '/pacientes/create', '/pacientes/1', '/pacientes/1/edit', '/pacientes/1/ficha',
            '/presupuestos', '/presupuestos?estado=aceptado', '/presupuestos/create?paciente_id=1', '/presupuestos/1', '/presupuestos/1/imprimir',
            '/usuarios', '/usuarios/create', '/usuarios/1/edit', '/usuarios/2/edit',
            '/doctores', '/doctores/create', '/doctores/1/edit',
            '/sucursales', '/sucursales/create', '/sucursales/1/edit',
            '/box', '/box/create', '/box/1/edit',
            '/convenios', '/convenios/create', '/convenios/1/edit',
            '/tratamientos', '/tratamientos/create', '/tratamientos/1/edit',
            '/dashboard', '/dashboard?mes=2026-10', '/reportes', '/auditoria',
            '/citas/1/cancelar', '/presupuestos/1/anular', '/pagos/1/anular', '/sucursales/1/eliminar', '/convenios/1/eliminar',
        ];

        foreach ($rutas as $ruta) {
            $this->actingAs($admin)->get($ruta)->assertOk();
        }
    }

    public function test_pantallas_de_dentista_y_recepcionista(): void
    {
        $dentista = Usuario::where('email', 'camila@dientefeliz.test')->first();
        $recepcionista = Usuario::where('email', 'valentina@dientefeliz.test')->first();

        foreach (['/', '/agenda', '/pacientes/1', '/pacientes/1/edit', '/pacientes/1/ficha', '/presupuestos/1', '/citas/1/reagendar'] as $ruta) {
            $this->actingAs($dentista)->get($ruta)->assertOk();
        }

        foreach (['/', '/agenda', '/pacientes/1', '/pacientes/create', '/presupuestos', '/presupuestos/create', '/presupuestos/1'] as $ruta) {
            $this->actingAs($recepcionista)->get($ruta)->assertOk();
        }
    }

    public function test_presupuesto_en_borrador_se_puede_editar(): void
    {
        $recepcionista = Usuario::where('email', 'valentina@dientefeliz.test')->first();

        $this->actingAs($recepcionista)->post('/presupuestos', [
            'paciente_id' => 2, 'doctor_id' => 1, 'cantidades' => [1 => 1],
        ]);

        $this->actingAs($recepcionista)->get('/presupuestos/2/edit')->assertOk()->assertSee('Editar presupuesto');
    }
}
