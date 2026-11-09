<?php

namespace Tests\Feature;

use App\Models\Bloqueo;
use App\Models\Box;
use App\Models\Cita;
use App\Models\Sucursal;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaAvanzadaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private string $dia;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dia = Cita::find(1)->hora_inicio->toDateString();
    }

    private function usuario(string $nombre): Usuario
    {
        return Usuario::where('email', "$nombre@dientefeliz.test")->first();
    }

    private function agendar(array $datos)
    {
        return $this->actingAs($this->usuario('valentina'))->post('/citas', array_merge([
            'paciente_id' => 2, 'doctor_id' => 1, 'box_id' => 1, 'fecha' => $this->dia, 'hora' => '15:00', 'duracion' => 30,
        ], $datos));
    }

    public function test_bloqueo_de_doctor_impide_agendar_y_avisa_citas_afectadas(): void
    {
        $this->actingAs($this->usuario('admin'))->post('/bloqueos', [
            'doctor_id' => 1, 'fecha_inicio' => $this->dia, 'fecha_fin' => $this->dia, 'motivo' => 'Vacaciones',
        ])->assertRedirect('/bloqueos')->assertSessionHas('exito', 'Bloqueo creado. Hay 2 citas vigentes en ese período que deben reagendarse.');

        $this->agendar([])->assertSessionHasErrors(['hora' => 'No se puede agendar. Dra. Camila Rojas tiene un bloqueo '.Carbon::parse($this->dia)->format('d/m/Y').': Vacaciones.']);
        $this->agendar(['doctor_id' => 2, 'box_id' => 2])->assertSessionHasNoErrors();
    }

    public function test_bloqueo_de_box_y_feriado_de_toda_la_clinica(): void
    {
        Bloqueo::create(['box_id' => 2, 'fecha_inicio' => $this->dia, 'fecha_fin' => $this->dia, 'motivo' => 'Mantención']);

        $this->agendar(['doctor_id' => 2, 'box_id' => 2])->assertSessionHasErrors('hora');
        $this->agendar(['doctor_id' => 2, 'box_id' => 1, 'hora' => '16:00'])->assertSessionHasNoErrors();

        $siguiente = Carbon::parse($this->dia)->addWeek()->toDateString();
        Bloqueo::create(['fecha_inicio' => $siguiente, 'fecha_fin' => $siguiente, 'motivo' => 'Feriado']);

        $this->agendar(['fecha' => $siguiente])->assertSessionHasErrors(['hora' => 'No se puede agendar. Toda la clínica tiene un bloqueo '.Carbon::parse($siguiente)->format('d/m/Y').': Feriado.']);
    }

    public function test_agenda_muestra_el_bloqueo_y_al_eliminarlo_se_puede_agendar(): void
    {
        $bloqueo = Bloqueo::create(['doctor_id' => 1, 'fecha_inicio' => $this->dia, 'fecha_fin' => $this->dia, 'motivo' => 'Congreso']);

        $this->actingAs($this->usuario('valentina'))->get('/agenda?fecha='.$this->dia)
            ->assertOk()->assertSee('Congreso');

        $this->actingAs($this->usuario('admin'))->get("/bloqueos/{$bloqueo->id}/eliminar")->assertOk();
        $this->actingAs($this->usuario('admin'))->delete("/bloqueos/{$bloqueo->id}", ['motivo' => 'Se suspendió el congreso'])
            ->assertRedirect('/bloqueos');

        $this->agendar([])->assertSessionHasNoErrors();
    }

    public function test_bloqueo_con_fechas_invertidas_se_rechaza(): void
    {
        $this->actingAs($this->usuario('admin'))->post('/bloqueos', [
            'fecha_inicio' => '2026-12-10', 'fecha_fin' => '2026-12-01', 'motivo' => 'Error',
        ])->assertSessionHasErrors('fecha_fin');
    }

    public function test_solo_el_administrador_gestiona_bloqueos(): void
    {
        $this->actingAs($this->usuario('valentina'))->get('/bloqueos')->assertForbidden();
        $this->actingAs($this->usuario('camila'))->get('/bloqueos/create')->assertForbidden();
        $this->actingAs($this->usuario('admin'))->get('/bloqueos')->assertOk();
        $this->actingAs($this->usuario('admin'))->get('/bloqueos/create')->assertOk();
    }

    public function test_vista_semanal_muestra_las_citas_de_la_semana(): void
    {
        $this->actingAs($this->usuario('valentina'))->get('/agenda?vista=semana&fecha='.$this->dia)
            ->assertOk()
            ->assertSee('Semana del')
            ->assertSee('María Contreras')
            ->assertSee('Luis Vera')
            ->assertDontSee('Citas del día');
    }

    public function test_filtro_por_sucursal(): void
    {
        $centro = Sucursal::create(['nombre' => 'Sucursal Centro', 'direccion' => 'Calle 1']);
        $box3 = Box::create(['nombre' => 'Box 3', 'sucursal_id' => $centro->id]);
        $inicio = Carbon::parse($this->dia.' 15:00');
        Cita::create(['paciente_id' => 2, 'doctor_id' => 2, 'box_id' => $box3->id, 'hora_inicio' => $inicio, 'hora_fin' => $inicio->copy()->addMinutes(30)]);

        $respuesta = $this->actingAs($this->usuario('valentina'))->get("/agenda?fecha={$this->dia}&sucursal_id={$centro->id}")
            ->assertOk()
            ->assertSee('Box 3');

        $this->assertSame(1, substr_count($respuesta->getContent(), 'class="agenda-cita"'));
        $this->assertSame(1, substr_count($respuesta->getContent(), 'citas/'.Cita::latest('id')->first()->id.'/reagendar'));
    }
}
