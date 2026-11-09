<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdministracionTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function usuario(string $nombre): Usuario
    {
        return Usuario::where('email', "$nombre@dientefeliz.test")->first();
    }

    public function test_dashboard_reportes_y_auditoria_son_solo_del_administrador(): void
    {
        foreach (['/dashboard', '/reportes', '/auditoria'] as $ruta) {
            $this->actingAs($this->usuario('admin'))->get($ruta)->assertOk();
            $this->actingAs($this->usuario('valentina'))->get($ruta)->assertForbidden();
            $this->actingAs($this->usuario('camila'))->get($ruta)->assertForbidden();
        }
    }

    public function test_indicadores_coinciden_con_sql_directo(): void
    {
        $mes = Carbon::parse('2026-10-01');
        $datos = app(DashboardService::class)->indicadores($mes);

        $ingresosMes = DB::selectOne("SELECT COALESCE(SUM(monto), 0) AS total FROM pagos
            WHERE estado = 'vigente' AND fecha BETWEEN '2026-10-01' AND '2026-10-31'")->total;
        $this->assertEquals($ingresosMes, $datos['caja']['mes']);

        $porCobrar = DB::selectOne("SELECT SUM(p.total_calculado - COALESCE(
                (SELECT SUM(monto) FROM pagos WHERE presupuesto_id = p.id AND estado = 'vigente'), 0)) AS total
            FROM presupuestos p WHERE p.estado = 'aceptado'")->total;
        $this->assertEquals($porCobrar, $datos['caja']['porCobrar']);

        $this->assertEquals(60000, $datos['porEspecialista'][0]['total']);
        $this->assertSame('Dra. Camila Rojas', $datos['porEspecialista'][0]['nombre']);
    }

    public function test_reporte_se_exporta_a_csv_para_excel(): void
    {
        $respuesta = $this->actingAs($this->usuario('admin'))
            ->get('/reportes?tipo=ventas&desde=2026-10-01&hasta=2026-10-31&formato=csv')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $contenido = $respuesta->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenido);
        $this->assertStringContainsString('Fecha;"Presupuesto N.°";RUT;Paciente;Doctor;Monto;Método;Estado', $contenido);
        $this->assertStringContainsString('María Contreras', $contenido);
    }

    public function test_todos_los_reportes_se_generan(): void
    {
        foreach (['doctores', 'box', 'sucursales', 'pacientes', 'ventas'] as $tipo) {
            $this->actingAs($this->usuario('admin'))->get("/reportes?tipo={$tipo}")->assertOk();
            $this->actingAs($this->usuario('admin'))->get("/reportes?tipo={$tipo}&formato=imprimir")->assertOk();
        }
    }

    public function test_alertas_de_inicio_para_recepcion(): void
    {
        $this->actingAs($this->usuario('valentina'))->get('/')
            ->assertOk()
            ->assertSee('Presupuestos aceptados con saldo pendiente')
            ->assertSee('María Contreras');
    }
}
