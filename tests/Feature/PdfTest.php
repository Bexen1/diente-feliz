<?php

namespace Tests\Feature;

use App\Models\AuditoriaLog;
use App\Models\Paciente;
use App\Models\Pago;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function usuario(string $nombre): Usuario
    {
        return Usuario::where('email', "$nombre@dientefeliz.test")->first();
    }

    private function esPdf($respuesta, string $archivo): void
    {
        $respuesta->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="'.$archivo.'"');
        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
    }

    public function test_dentista_descarga_la_ficha_en_pdf_y_queda_auditado(): void
    {
        $paciente = Paciente::find(1);

        $this->esPdf($this->actingAs($this->usuario('camila'))->get('/pacientes/1/ficha/exportar/pdf'),
            'ficha-'.$paciente->rut.'-'.now()->format('Ymd').'.pdf');

        $registro = AuditoriaLog::where('accion', 'exportar')->latest('id')->first();
        $this->assertSame('pdf', $registro->payload_cambios_json['despues']['formato']);
    }

    public function test_recepcionista_no_descarga_la_ficha(): void
    {
        $this->actingAs($this->usuario('valentina'))->get('/pacientes/1/ficha/exportar/pdf')->assertForbidden();
    }

    public function test_reporte_en_pdf(): void
    {
        $desde = now()->startOfMonth()->toDateString();
        $hasta = now()->endOfMonth()->toDateString();

        $this->esPdf(
            $this->actingAs($this->usuario('admin'))->get("/reportes?tipo=ventas&desde={$desde}&hasta={$hasta}&formato=pdf"),
            'reporte-ventas-'.now()->startOfMonth()->format('Ymd').'-'.now()->endOfMonth()->format('Ymd').'.pdf'
        );
    }

    public function test_orden_de_tratamiento_y_comprobante_en_pdf(): void
    {
        $this->esPdf($this->actingAs($this->usuario('valentina'))->get('/presupuestos/1/pdf'), 'orden-tratamiento-1.pdf');

        $pago = Pago::first();
        $this->esPdf($this->actingAs($this->usuario('valentina'))->get("/pagos/{$pago->id}/comprobante/pdf"),
            'comprobante-'.str_pad($pago->id, 6, '0', STR_PAD_LEFT).'.pdf');
    }

    public function test_las_pantallas_ofrecen_descargar_pdf(): void
    {
        $this->actingAs($this->usuario('camila'))->get('/pacientes/1/ficha')
            ->assertSee(route('fichas.exportar.pdf', 1));
        $this->actingAs($this->usuario('admin'))->get('/reportes')
            ->assertSee('formato=pdf', false);
    }
}
