<?php

namespace Tests\Feature;

use App\Models\Pago;
use App\Models\Usuario;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetodoPagoTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function usuario(string $nombre): Usuario
    {
        return Usuario::where('email', "$nombre@dientefeliz.test")->first();
    }

    public function test_el_pago_guarda_su_metodo_y_ofrece_el_comprobante(): void
    {
        $this->actingAs($this->usuario('valentina'))
            ->post('/presupuestos/1/pagos', ['monto' => 10000, 'fecha' => now()->toDateString(), 'metodo' => 'transferencia'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('comprobante');

        $pago = Pago::latest('id')->first();
        $this->assertSame('transferencia', $pago->metodo);

        $this->actingAs($this->usuario('valentina'))->get('/presupuestos/1')
            ->assertSee('Imprimir comprobante')
            ->assertSee('Transferencia');
    }

    public function test_metodo_invalido_se_rechaza(): void
    {
        $this->actingAs($this->usuario('valentina'))
            ->post('/presupuestos/1/pagos', ['monto' => 10000, 'fecha' => now()->toDateString(), 'metodo' => 'cheque'])
            ->assertSessionHasErrors('metodo');
    }

    public function test_comprobante_muestra_monto_metodo_y_saldo(): void
    {
        $this->actingAs($this->usuario('valentina'))->get('/pagos/1/comprobante')
            ->assertOk()
            ->assertSee('Comprobante de pago')
            ->assertSee('$40.000')
            ->assertSee('Efectivo')
            ->assertSee('$42.000');

        Pago::where('id', 1)->update(['estado' => 'anulado']);
        $this->actingAs($this->usuario('valentina'))->get('/pagos/1/comprobante')->assertSee('ANULADO');
    }

    public function test_dentista_no_ve_comprobantes(): void
    {
        $this->actingAs($this->usuario('camila'))->get('/pagos/1/comprobante')->assertForbidden();
    }

    public function test_dashboard_separa_ingresos_por_metodo(): void
    {
        Pago::where('id', 2)->update(['metodo' => 'debito']);

        $porMetodo = collect(app(DashboardService::class)->indicadores(now()->setDate(2026, 10, 1))['porMetodo'])->keyBy('metodo');

        $this->assertEquals(40000, $porMetodo['Efectivo']['total']);
        $this->assertEquals(20000, $porMetodo['Tarjeta de débito']['total']);
        $this->assertEquals(0, $porMetodo['Transferencia']['total']);
    }
}
