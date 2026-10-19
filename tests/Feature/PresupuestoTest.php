<?php

namespace Tests\Feature;

use App\Models\AuditoriaLog;
use App\Models\Pago;
use App\Models\Presupuesto;
use App\Models\Tratamiento;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresupuestoTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function usuario(string $nombre): Usuario
    {
        return Usuario::where('email', "$nombre@dientefeliz.test")->first();
    }

    private function cantidades(): array
    {
        return [
            Tratamiento::where('nombre', 'Restauración con resina')->value('id') => 2,
            Tratamiento::where('nombre', 'Extracción simple')->value('id') => 1,
        ];
    }

    public function test_total_aplica_descuento_del_convenio(): void
    {
        $this->actingAs($this->usuario('valentina'))->post('/presupuestos', [
            'paciente_id' => 1, 'doctor_id' => 1, 'cantidades' => $this->cantidades(),
        ])->assertSessionHasNoErrors();

        $presupuesto = Presupuesto::latest('id')->first();
        $this->assertEquals(102000, $presupuesto->total_calculado);
        $this->assertEquals(120000, $presupuesto->subtotal());
        $this->assertSame('borrador', $presupuesto->estado);
    }

    public function test_total_sin_convenio(): void
    {
        $this->actingAs($this->usuario('valentina'))->post('/presupuestos', [
            'paciente_id' => 2, 'doctor_id' => 1, 'cantidades' => $this->cantidades(),
        ]);

        $this->assertEquals(120000, Presupuesto::latest('id')->first()->total_calculado);
    }

    public function test_los_montos_no_se_aceptan_desde_el_navegador(): void
    {
        $this->actingAs($this->usuario('valentina'))->post('/presupuestos', [
            'paciente_id' => 2, 'doctor_id' => 1, 'cantidades' => $this->cantidades(), 'total_calculado' => 1,
        ]);

        $this->assertEquals(120000, Presupuesto::latest('id')->first()->total_calculado);
    }

    public function test_saldo_se_calcula_con_los_pagos(): void
    {
        $this->assertEquals(42000, Presupuesto::find(1)->saldoPendiente());
    }

    public function test_pago_mayor_que_el_saldo_se_rechaza(): void
    {
        $this->actingAs($this->usuario('valentina'))
            ->post('/presupuestos/1/pagos', ['monto' => 50000, 'fecha' => now()->toDateString()])
            ->assertSessionHasErrors(['monto' => 'El monto supera el saldo pendiente de $42.000.']);

        $this->assertSame(2, Pago::count());
    }

    public function test_pago_del_saldo_completo_marca_pagado(): void
    {
        $this->actingAs($this->usuario('valentina'))
            ->post('/presupuestos/1/pagos', ['monto' => 42000, 'fecha' => now()->toDateString()])
            ->assertSessionHasNoErrors();

        $presupuesto = Presupuesto::find(1);
        $this->assertEquals(0, $presupuesto->saldoPendiente());
        $this->assertSame('pagado', $presupuesto->estado);
    }

    public function test_solo_el_administrador_anula_pagos_y_el_saldo_se_recupera(): void
    {
        $this->actingAs($this->usuario('valentina'))->get('/pagos/2/anular')->assertForbidden();
        $this->actingAs($this->usuario('valentina'))->patch('/pagos/2/anular', ['motivo' => 'Error de digitación'])->assertForbidden();

        $this->actingAs($this->usuario('admin'))->get('/pagos/2/anular')
            ->assertOk()
            ->assertSee('$20.000')
            ->assertDontSee('$20.000</textarea>', false);
        $this->actingAs($this->usuario('admin'))->patch('/pagos/2/anular', ['motivo' => 'Error de digitación'])->assertRedirect();

        $this->assertSame('anulado', Pago::find(2)->estado);
        $this->assertEquals(62000, Presupuesto::find(1)->saldoPendiente());
        $this->assertSame('Error de digitación', AuditoriaLog::where('tabla_afectada', 'pagos')
            ->where('accion', 'anular')->first()->payload_cambios_json['despues']['motivo']);
    }

    public function test_anular_sin_motivo_no_hace_nada(): void
    {
        $this->actingAs($this->usuario('admin'))->patch('/pagos/2/anular', ['motivo' => ''])
            ->assertSessionHasErrors('motivo');

        $this->assertSame('vigente', Pago::find(2)->estado);
    }

    public function test_presupuesto_con_pagos_vigentes_no_se_anula(): void
    {
        $this->actingAs($this->usuario('valentina'))->get('/presupuestos/1/anular')->assertOk();

        $this->actingAs($this->usuario('valentina'))
            ->from('/presupuestos/1/anular')
            ->patch('/presupuestos/1/anular', ['motivo' => 'El paciente desiste'])
            ->assertRedirect('/presupuestos/1/anular')
            ->assertSessionHasErrors('estado');

        $this->assertSame('aceptado', Presupuesto::find(1)->estado);
    }

    public function test_dentista_consulta_presupuestos_pero_no_registra_pagos(): void
    {
        $dentista = $this->usuario('camila');

        $this->actingAs($dentista)->get('/presupuestos/1')->assertOk()->assertDontSee('Registrar pago');
        $this->actingAs($dentista)->post('/presupuestos/1/pagos', ['monto' => 1000, 'fecha' => now()->toDateString()])->assertForbidden();
        $this->actingAs($dentista)->get('/presupuestos/create')->assertForbidden();
    }

    public function test_presupuesto_aceptado_no_se_edita(): void
    {
        $this->actingAs($this->usuario('valentina'))
            ->put('/presupuestos/1', ['doctor_id' => 1, 'cantidades' => $this->cantidades()])
            ->assertSessionHasErrors('estado');
    }

    public function test_orden_imprimible_muestra_total_y_saldo(): void
    {
        $this->actingAs($this->usuario('valentina'))->get('/presupuestos/1/imprimir')
            ->assertOk()
            ->assertSee('Orden de tratamiento')
            ->assertSee('$102.000')
            ->assertSee('$42.000');
    }
}
