<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Pago;
use App\Models\Presupuesto;
use App\Models\Usuario;
use App\Services\DashboardService;
use Carbon\Carbon;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function indicadores(Carbon $mes): array
    {
        return app(DashboardService::class)->indicadores($mes);
    }

    public function test_indicadores_financieros_del_mes(): void
    {
        $datos = $this->indicadores(now());

        $this->assertSame(1, $datos['aceptacion']['emitidos']);
        $this->assertSame(100, $datos['aceptacion']['tasa']);
        $this->assertEquals(102000, $datos['aceptacion']['promedio']);

        $this->assertSame('Caja de Compensación Andes', $datos['descuentos'][0]['institucion']);
        $this->assertEquals(18000, $datos['descuentos'][0]['descuento']);

        $this->assertSame('Restauración con resina', $datos['tratamientos'][0]['nombre']);
        $this->assertSame(2, $datos['tratamientos'][0]['cantidad']);
        $this->assertEquals(90000, $datos['tratamientos'][0]['monto']);
    }

    public function test_ingresos_de_los_ultimos_seis_meses(): void
    {
        $tendencia = $this->indicadores(Carbon::parse('2026-10-01'))['ingresosMensuales'];

        $this->assertCount(6, $tendencia['meses']);
        $this->assertSame('Oct', $tendencia['meses'][5]['etiqueta']);
        $this->assertEquals(60000, $tendencia['meses'][5]['total']);
        $this->assertEquals(0, $tendencia['meses'][4]['total']);
        $this->assertNull($tendencia['variacion']);
    }

    public function test_mes_en_curso_se_compara_con_el_mismo_periodo_del_mes_anterior(): void
    {
        $inicioAnterior = today()->subMonth()->startOfMonth();
        DB::table('pagos')->insert([
            ['presupuesto_id' => 1, 'monto' => 10000, 'fecha' => today()->toDateString(), 'estado' => 'vigente'],
            ['presupuesto_id' => 1, 'monto' => 5000, 'fecha' => $inicioAnterior->toDateString(), 'estado' => 'vigente'],
            ['presupuesto_id' => 1, 'monto' => 7000, 'fecha' => $inicioAnterior->copy()->endOfMonth()->toDateString(), 'estado' => 'vigente'],
        ]);

        $tendencia = $this->indicadores(now())['ingresosMensuales'];

        $this->assertSame('mismo período del mes anterior', $tendencia['comparacion']);
        $anterior = 5000 + (today()->day >= $inicioAnterior->copy()->endOfMonth()->day ? 7000 : 0);
        $this->assertSame((int) round((10000 - $anterior) / $anterior * 100), $tendencia['variacion']);
    }

    public function test_pacientes_por_convenio_y_del_mes(): void
    {
        $datos = $this->indicadores(now());
        $porCategoria = collect($datos['pacientesPorConvenio'])->pluck('total', 'categoria');

        $this->assertSame(2, $porCategoria['Caja de Compensación Andes']);
        $this->assertSame(2, $porCategoria['Particular']);
        $this->assertSame(4, $datos['pacientesMes']['nuevos']);
        $this->assertSame(4, $datos['pacientesMes']['activos']);
    }

    public function test_agenda_ocupacion_cancelacion_demanda_y_sucursales(): void
    {
        $dia = Cita::find(1)->hora_inicio;
        $datos = $this->indicadores($dia->copy());

        $ocupacion = collect($datos['ocupacionBox'])->keyBy('box');
        $this->assertEquals(2.3, $ocupacion['Box 1']['horas']);
        $this->assertEquals(0.5, $ocupacion['Box 2']['horas']);

        $this->assertSame(4, $datos['cancelacion']['total']);
        $this->assertEquals(0, $datos['cancelacion']['tasa']);

        $this->assertSame(2, $datos['demanda']['matriz'][$dia->dayOfWeekIso][9]);
        $this->assertSame(1, $datos['demanda']['matriz'][$dia->dayOfWeekIso][11]);

        $this->assertSame('Casa Matriz', $datos['sucursales'][0]['nombre']);
        $this->assertSame(4, $datos['sucursales'][0]['citas']);

        Cita::where('id', 4)->update(['estado' => 'cancelada']);
        $this->assertEquals(25, $this->indicadores($dia->copy())['cancelacion']['tasa']);
    }

    public function test_saldos_antiguos(): void
    {
        $this->assertSame(0, $this->indicadores(now())['saldosAntiguos']['cantidad']);

        Presupuesto::where('id', 1)->update(['created_at' => now()->subDays(40)]);
        $saldos = $this->indicadores(now())['saldosAntiguos'];

        $this->assertSame(1, $saldos['cantidad']);
        $this->assertEquals(42000, $saldos['monto']);
        $this->assertSame('María Contreras', $saldos['lista'][0]['paciente']);
    }

    public function test_dashboard_muestra_los_nuevos_indicadores(): void
    {
        $admin = Usuario::where('email', 'admin@dientefeliz.test')->first();

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('Ingresos de los últimos 6 meses')
            ->assertSee('Tasa de aceptación')
            ->assertSee('Pacientes por convenio')
            ->assertSee('Tratamientos más solicitados')
            ->assertSee('Ocupación por box')
            ->assertSee('Tasa de cancelación')
            ->assertSee('Días y horas de mayor demanda')
            ->assertSee('Descuentos por convenio')
            ->assertSee('Actividad por sucursal')
            ->assertSee('Saldos pendientes más antiguos')
            ->assertSee('Actualizado a las');
    }

    public function test_datos_de_demostracion_se_cargan_sin_errores(): void
    {
        $this->seed(DemoSeeder::class);

        $this->assertGreaterThan(300, Cita::count());
        $this->assertGreaterThan(20, Presupuesto::count());
        $this->assertGreaterThanOrEqual(44, Paciente::count());

        $cruces = DB::selectOne("SELECT COUNT(*) AS total FROM citas a JOIN citas b ON a.id < b.id
            AND a.estado <> 'cancelada' AND b.estado <> 'cancelada'
            AND (a.doctor_id = b.doctor_id OR a.box_id = b.box_id)
            AND a.hora_inicio < b.hora_fin AND b.hora_inicio < a.hora_fin")->total;
        $this->assertSame(0, (int) $cruces);

        $pagadosIncompletos = Presupuesto::where('estado', 'pagado')->get()
            ->filter(fn ($p) => $p->saldoPendiente() != 0)->count();
        $this->assertSame(0, $pagadosIncompletos);

        $this->assertSame(0, Pago::where('fecha', '>', today())->count());
        $this->assertSame(0, Presupuesto::where('estado', 'aceptado')->get()->filter(fn ($p) => $p->saldoPendiente() < 0)->count());

        $meses = $this->indicadores(now())['ingresosMensuales']['meses'];
        $this->assertGreaterThanOrEqual(5, collect($meses)->where('total', '>', 0)->count());

        $admin = Usuario::where('email', 'admin@dientefeliz.test')->first();
        foreach (['/dashboard', '/dashboard?mes='.now()->subMonths(3)->format('Y-m'), '/agenda', '/reportes?tipo=box', '/presupuestos', '/'] as $ruta) {
            $this->actingAs($admin)->get($ruta)->assertOk();
        }
    }
}
