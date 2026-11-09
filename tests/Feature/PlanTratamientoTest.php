<?php

namespace Tests\Feature;

use App\Models\AuditoriaLog;
use App\Models\DetallePresupuesto;
use App\Models\Presupuesto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanTratamientoTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function usuario(string $nombre): Usuario
    {
        return Usuario::where('email', "$nombre@dientefeliz.test")->first();
    }

    public function test_la_ficha_muestra_el_plan_con_su_avance(): void
    {
        $this->actingAs($this->usuario('camila'))->get('/pacientes/1/ficha')
            ->assertOk()
            ->assertSee('Plan de tratamiento')
            ->assertSee('Presupuesto N.° 1')
            ->assertSee('0 de 3 realizados');
    }

    public function test_registrar_realizado_avanza_el_plan_y_deja_atencion_en_la_ficha(): void
    {
        $resina = DetallePresupuesto::where('presupuesto_id', 1)->where('cantidad', 2)->first();

        $this->actingAs($this->usuario('camila'))->post("/plan/{$resina->id}/realizado")
            ->assertRedirect('/pacientes/1/ficha');

        $this->assertSame(1, $resina->fresh()->cantidad_realizada);
        $evolucion = $resina->presupuesto->paciente->ficha->evoluciones()->first();
        $this->assertStringContainsString('Restauración con resina (1 de 2)', $evolucion->tratamiento_realizado);
        $this->assertTrue(AuditoriaLog::where('accion', 'registrar_realizado')->exists());

        $this->actingAs($this->usuario('camila'))->get('/pacientes/1/ficha')->assertSee('1 de 3 realizados');
    }

    public function test_no_se_supera_la_cantidad_presupuestada(): void
    {
        $extraccion = DetallePresupuesto::where('presupuesto_id', 1)->where('cantidad', 1)->first();

        $this->actingAs($this->usuario('camila'))->post("/plan/{$extraccion->id}/realizado");
        $this->actingAs($this->usuario('camila'))->post("/plan/{$extraccion->id}/realizado")
            ->assertSessionHasErrors('detalle');

        $this->assertSame(1, $extraccion->fresh()->cantidad_realizada);
    }

    public function test_presupuesto_en_borrador_no_admite_registros(): void
    {
        Presupuesto::where('id', 1)->update(['estado' => 'borrador']);
        $detalle = DetallePresupuesto::where('presupuesto_id', 1)->first();

        $this->actingAs($this->usuario('camila'))->post("/plan/{$detalle->id}/realizado")
            ->assertSessionHasErrors('detalle');
    }

    public function test_recepcionista_no_registra_tratamientos_realizados(): void
    {
        $detalle = DetallePresupuesto::where('presupuesto_id', 1)->first();

        $this->actingAs($this->usuario('valentina'))->post("/plan/{$detalle->id}/realizado")->assertForbidden();
    }
}
