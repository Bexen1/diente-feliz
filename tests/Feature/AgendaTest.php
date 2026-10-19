<?php

namespace Tests\Feature;

use App\Models\AuditoriaLog;
use App\Models\Cita;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaTest extends TestCase
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

    private function agendar(array $datos, string $quien = 'valentina')
    {
        return $this->actingAs($this->usuario($quien))->post('/citas', array_merge([
            'paciente_id' => 2, 'doctor_id' => 1, 'box_id' => 2,
            'fecha' => $this->dia, 'hora' => '12:00', 'duracion' => 30,
        ], $datos));
    }

    public function test_cita_valida_se_agenda_y_se_audita(): void
    {
        $this->agendar(['hora' => '12:00', 'duracion' => 45])->assertSessionHasNoErrors();

        $cita = Cita::latest('id')->first();
        $this->assertSame('12:45', $cita->hora_fin->format('H:i'));
        $this->assertTrue(AuditoriaLog::where('accion', 'crear')->where('tabla_afectada', 'citas')->exists());
    }

    public function test_duracion_distinta_de_30_45_60_se_rechaza(): void
    {
        $this->agendar(['duracion' => 50])->assertSessionHasErrors('duracion');
    }

    public function test_cruce_por_doctor_se_rechaza_con_mensaje(): void
    {
        $antes = Cita::count();

        $this->agendar(['doctor_id' => 1, 'box_id' => 2, 'hora' => '09:30'])
            ->assertSessionHasErrors(['hora' => 'No se puede agendar. Dra. Camila Rojas ya tiene una cita entre 09:00 y 09:45.']);

        $this->assertSame($antes, Cita::count());
    }

    public function test_cruce_por_box_se_rechaza(): void
    {
        $this->agendar(['doctor_id' => 2, 'box_id' => 1, 'hora' => '09:15'])
            ->assertSessionHasErrors('hora');
    }

    public function test_cita_contigua_se_acepta(): void
    {
        $this->agendar(['doctor_id' => 1, 'box_id' => 1, 'hora' => '09:45', 'duracion' => 30, 'paciente_id' => 3])
            ->assertSessionHasErrors('hora');

        $this->agendar(['doctor_id' => 1, 'box_id' => 1, 'hora' => '10:30', 'duracion' => 30])
            ->assertSessionHasNoErrors();
    }

    public function test_cita_cancelada_libera_el_horario(): void
    {
        $this->actingAs($this->usuario('valentina'))
            ->patch('/citas/1/estado', ['estado' => 'cancelada', 'motivo' => 'El paciente avisó que no puede asistir'])
            ->assertSessionHasNoErrors();

        $this->agendar(['doctor_id' => 1, 'box_id' => 1, 'hora' => '09:00'])->assertSessionHasNoErrors();
    }

    public function test_cita_fuera_del_horario_del_doctor_se_rechaza(): void
    {
        $this->agendar(['hora' => '18:00', 'duracion' => 30])->assertSessionHasErrors('hora');

        $sabado = Carbon::parse($this->dia)->next(Carbon::SATURDAY)->toDateString();
        $this->agendar(['fecha' => $sabado, 'hora' => '10:00'])->assertSessionHasErrors('hora');
    }

    public function test_reagendar_audita_valor_anterior_y_nuevo(): void
    {
        $this->actingAs($this->usuario('valentina'))->put('/citas/1', [
            'doctor_id' => 1, 'box_id' => 1, 'fecha' => $this->dia, 'hora' => '15:00', 'duracion' => 45,
        ])->assertSessionHasNoErrors();

        $this->assertSame('reagendada', Cita::find(1)->estado);

        $log = AuditoriaLog::where('accion', 'reagendar')->first();
        $this->assertSame($this->dia.' 09:00', $log->payload_cambios_json['antes']['hora_inicio']);
        $this->assertSame($this->dia.' 15:00', $log->payload_cambios_json['despues']['hora_inicio']);
    }

    public function test_dentista_solo_gestiona_su_propia_agenda(): void
    {
        $this->actingAs($this->usuario('camila'))->get('/citas/2/reagendar')->assertForbidden();
        $this->actingAs($this->usuario('camila'))->patch('/citas/2/estado', ['estado' => 'cancelada'])->assertForbidden();
        $this->agendar(['doctor_id' => 2], 'camila')->assertForbidden();

        $this->actingAs($this->usuario('camila'))->get('/agenda?fecha='.$this->dia.'&doctor_id=2')
            ->assertOk()
            ->assertSee('Dra. Camila Rojas')
            ->assertDontSee('Dr. Matías Soto');
    }

    public function test_estado_final_no_se_puede_cambiar(): void
    {
        $this->actingAs($this->usuario('valentina'))->patch('/citas/1/estado', ['estado' => 'atendida']);

        $this->actingAs($this->usuario('valentina'))
            ->patch('/citas/1/estado', ['estado' => 'cancelada', 'motivo' => 'Prueba de estado final'])
            ->assertSessionHasErrors('estado');
    }

    public function test_cancelar_pide_confirmacion_con_motivo_y_lo_audita(): void
    {
        $recepcionista = $this->usuario('valentina');

        $this->actingAs($recepcionista)->get('/citas/1/cancelar')
            ->assertOk()->assertSee('Cancelar cita')->assertSee('María Contreras');

        $this->actingAs($recepcionista)->patch('/citas/1/estado', ['estado' => 'cancelada'])
            ->assertSessionHasErrors('motivo');
        $this->assertSame('agendada', Cita::find(1)->estado);

        $this->actingAs($recepcionista)->patch('/citas/1/estado', ['estado' => 'cancelada', 'motivo' => 'Paciente enfermo']);

        $this->assertSame('cancelada', Cita::find(1)->estado);
        $log = AuditoriaLog::where('accion', 'cancelar')->first();
        $this->assertSame('Paciente enfermo', $log->payload_cambios_json['despues']['motivo']);
    }
}
