<?php

namespace Tests\Feature;

use App\Models\AuditoriaLog;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportacionFichaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function usuario(string $nombre): Usuario
    {
        return Usuario::where('email', "$nombre@dientefeliz.test")->first();
    }

    public function test_dentista_exporta_la_ficha_imprimible_y_queda_auditado(): void
    {
        $this->actingAs($this->usuario('camila'))->get('/pacientes/1/ficha/exportar')
            ->assertOk()
            ->assertSee('Copia de ficha clínica')
            ->assertSee('María Contreras')
            ->assertSee('Alergia a la penicilina')
            ->assertSee('$102.000');

        $log = AuditoriaLog::where('accion', 'exportar')->first();
        $this->assertSame('imprimible', $log->payload_cambios_json['despues']['formato']);
    }

    public function test_descarga_json_con_todos_los_datos(): void
    {
        $respuesta = $this->actingAs($this->usuario('camila'))->get('/pacientes/1/ficha/exportar/json')->assertOk();

        $this->assertStringContainsString('attachment; filename="ficha-12345678-5-', $respuesta->headers->get('Content-Disposition'));

        $datos = $respuesta->json();
        $this->assertSame('12345678-5', $datos['paciente']['rut']);
        $this->assertSame('Alergia a la penicilina', $datos['antecedentes_salud']['alergias']);
        $this->assertSame('caries', $datos['ficha']['odontograma']['16']['estado']);
        $this->assertEquals(102000, $datos['presupuestos'][0]['total']);
        $this->assertCount(2, $datos['presupuestos'][0]['pagos']);
        $this->assertCount(1, $datos['citas']);
    }

    public function test_recepcionista_no_puede_exportar_la_ficha(): void
    {
        $this->actingAs($this->usuario('valentina'))->get('/pacientes/1/ficha/exportar')->assertForbidden();
        $this->actingAs($this->usuario('valentina'))->get('/pacientes/1/ficha/exportar/json')->assertForbidden();
    }
}
