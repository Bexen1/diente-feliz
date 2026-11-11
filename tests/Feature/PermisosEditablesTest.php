<?php

namespace Tests\Feature;

use App\Models\AuditoriaLog;
use App\Models\Cita;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermisosEditablesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function usuario(string $nombre): Usuario
    {
        return Usuario::where('email', "$nombre@dientefeliz.test")->first();
    }

    private function rol(string $nombre): Rol
    {
        return Rol::where('nombre', $nombre)->first();
    }

    private function matrizActual(): array
    {
        $matriz = [];
        foreach (Rol::with('permisos')->where('nombre', '<>', Rol::ADMINISTRADOR)->get() as $rol) {
            $matriz[$rol->id] = $rol->clavesPermisos()->all();
        }

        return $matriz;
    }

    public function test_los_permisos_predeterminados_mantienen_el_comportamiento_original(): void
    {
        $this->assertEqualsCanonicalizing(
            ['pacientes.salud', 'fichas.gestionar', 'fichas.exportar', 'plan.registrar', 'presupuestos.ver'],
            $this->rol(Rol::DENTISTA)->clavesPermisos()->all()
        );
        $this->assertEqualsCanonicalizing(
            ['agenda.todas', 'presupuestos.ver', 'presupuestos.gestionar', 'pagos.registrar'],
            $this->rol(Rol::RECEPCIONISTA)->clavesPermisos()->all()
        );
        $this->assertSame(count(Permiso::CATALOGO), Permiso::count());
    }

    public function test_quitar_un_permiso_bloquea_la_pantalla_y_el_menu(): void
    {
        $matriz = $this->matrizActual();
        $dentista = $this->rol(Rol::DENTISTA)->id;
        $matriz[$dentista] = array_values(array_diff($matriz[$dentista], ['fichas.gestionar']));

        $this->actingAs($this->usuario('admin'))->put('/roles/permisos', ['permisos' => $matriz])
            ->assertRedirect('/roles')
            ->assertSessionHas('exito', 'Permisos actualizados para 1 rol.');

        $camila = $this->usuario('camila')->fresh();
        $this->actingAs($camila)->get('/pacientes/1/ficha')->assertForbidden();
        $this->actingAs($camila)->get('/pacientes/1')->assertOk()->assertDontSee('Ficha clínica</a>', false);

        $registro = AuditoriaLog::where('accion', 'actualizar_permisos')->latest('id')->first();
        $this->assertSame($dentista, $registro->registro_id);
        $this->assertContains('fichas.gestionar', $registro->payload_cambios_json['antes']['permisos']);
        $this->assertNotContains('fichas.gestionar', $registro->payload_cambios_json['despues']['permisos']);
    }

    public function test_dar_un_permiso_habilita_la_pantalla_y_el_menu(): void
    {
        $valentina = $this->usuario('valentina');
        $this->actingAs($valentina)->get('/dashboard')->assertForbidden();

        $matriz = $this->matrizActual();
        $matriz[$this->rol(Rol::RECEPCIONISTA)->id][] = 'dashboard.ver';
        $this->actingAs($this->usuario('admin'))->put('/roles/permisos', ['permisos' => $matriz]);

        $this->actingAs($valentina->fresh())->get('/dashboard')->assertOk();
        $this->actingAs($valentina->fresh())->get('/')->assertSee('href="'.route('dashboard').'"', false);
    }

    public function test_un_dentista_con_agenda_completa_gestiona_citas_de_otros(): void
    {
        $camila = $this->usuario('camila');
        $ajena = Cita::where('doctor_id', '<>', $camila->doctor->id)
            ->whereIn('estado', Cita::ESTADOS_VIGENTES)->first();
        $this->actingAs($camila)->get("/citas/{$ajena->id}/reagendar")->assertForbidden();

        $matriz = $this->matrizActual();
        $matriz[$this->rol(Rol::DENTISTA)->id][] = 'agenda.todas';
        $this->actingAs($this->usuario('admin'))->put('/roles/permisos', ['permisos' => $matriz]);

        $this->actingAs($camila->fresh())->get("/citas/{$ajena->id}/reagendar")->assertOk();
    }

    public function test_sin_cambios_no_audita(): void
    {
        $this->actingAs($this->usuario('admin'))->put('/roles/permisos', ['permisos' => $this->matrizActual()])
            ->assertSessionHas('exito', 'No hubo cambios en los permisos.');

        $this->assertFalse(AuditoriaLog::where('accion', 'actualizar_permisos')->exists());
    }

    public function test_rechaza_permisos_inexistentes_y_no_toca_al_administrador(): void
    {
        $admin = $this->rol(Rol::ADMINISTRADOR);

        $this->actingAs($this->usuario('admin'))->put('/roles/permisos', [
            'permisos' => [$this->rol(Rol::DENTISTA)->id => ['borrar.todo']],
        ])->assertSessionHasErrors('permisos.*');

        $this->actingAs($this->usuario('admin'))->put('/roles/permisos', ['permisos' => [$admin->id => []]]);
        $this->actingAs($this->usuario('admin'))->get('/auditoria')->assertOk();
    }

    public function test_solo_el_administrador_cambia_permisos(): void
    {
        $this->actingAs($this->usuario('valentina'))->put('/roles/permisos', [
            'permisos' => [$this->rol(Rol::RECEPCIONISTA)->id => array_keys(Permiso::CATALOGO)],
        ])->assertForbidden();

        $this->assertFalse($this->rol(Rol::RECEPCIONISTA)->clavesPermisos()->contains('auditoria.ver'));
    }
}
