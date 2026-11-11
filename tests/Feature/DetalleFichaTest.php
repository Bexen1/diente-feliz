<?php

namespace Tests\Feature;

use App\Models\DetallePresupuesto;
use App\Models\Evolucion;
use App\Models\Presupuesto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DetalleFichaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function usuario(string $nombre): Usuario
    {
        return Usuario::where('email', "$nombre@dientefeliz.test")->first();
    }

    private function detalleAceptado(): DetallePresupuesto
    {
        $presupuesto = Presupuesto::where('estado', 'aceptado')->first();

        return $presupuesto->detalles()->with('tratamiento')->first();
    }

    public function test_registrar_realizado_vincula_la_sesion_y_se_ve_en_el_detalle_del_tratamiento(): void
    {
        $detalle = $this->detalleAceptado();

        $this->actingAs($this->usuario('camila'))
            ->post("/plan/{$detalle->id}/realizado", ['volver' => 'detalle'])
            ->assertRedirect("/plan/{$detalle->id}");

        $sesion = Evolucion::where('detalle_presupuesto_id', $detalle->id)->first();
        $this->assertNotNull($sesion);

        $this->actingAs($this->usuario('camila'))->get("/plan/{$detalle->id}")
            ->assertOk()
            ->assertSee($detalle->tratamiento->nombre)
            ->assertSee('Sesiones realizadas')
            ->assertSee(route('atenciones.show', $sesion))
            ->assertSee('Presupuesto N.° '.$detalle->presupuesto_id);
    }

    public function test_detalle_de_la_atencion_muestra_su_tratamiento_y_navegacion(): void
    {
        $detalle = $this->detalleAceptado();
        $this->actingAs($this->usuario('camila'))->post("/plan/{$detalle->id}/realizado");
        $sesion = Evolucion::where('detalle_presupuesto_id', $detalle->id)->first();

        $this->actingAs($this->usuario('camila'))->get("/atenciones/{$sesion->id}")
            ->assertOk()
            ->assertSee('Detalle de la atención')
            ->assertSee($sesion->diagnostico)
            ->assertSee(route('detalles.show', $detalle))
            ->assertSee('Cambios en el odontograma ese día');
    }

    public function test_la_ficha_enlaza_a_los_detalles(): void
    {
        $detalle = $this->detalleAceptado();
        $paciente = $detalle->presupuesto->paciente_id;

        $this->actingAs($this->usuario('camila'))->post("/pacientes/{$paciente}/ficha/evoluciones", [
            'fecha' => now()->toDateString(), 'diagnostico' => 'Control de rutina',
        ]);
        $atencion = Evolucion::where('diagnostico', 'Control de rutina')->first();

        $this->actingAs($this->usuario('camila'))->get("/pacientes/{$paciente}/ficha")
            ->assertSee(route('detalles.show', $detalle))
            ->assertSee(route('atenciones.show', $atencion));
    }

    public function test_recepcionista_no_ve_detalles_clinicos(): void
    {
        $detalle = $this->detalleAceptado();

        $this->actingAs($this->usuario('valentina'))->get("/plan/{$detalle->id}")->assertForbidden();
    }
}
