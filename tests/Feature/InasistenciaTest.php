<?php

namespace Tests\Feature;

use App\Models\AuditoriaLog;
use App\Models\Cita;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InasistenciaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function usuario(string $nombre): Usuario
    {
        return Usuario::where('email', "$nombre@dientefeliz.test")->first();
    }

    private function citaPasada(int $id, int $minutosAtras): Cita
    {
        $inicio = now()->subMinutes($minutosAtras)->startOfMinute();
        Cita::where('id', $id)->update(['hora_inicio' => $inicio, 'hora_fin' => $inicio->copy()->addMinutes(30)]);

        return Cita::find($id);
    }

    public function test_no_se_marca_inasistencia_antes_de_la_tolerancia(): void
    {
        $this->citaPasada(3, 5);

        $this->actingAs($this->usuario('valentina'))
            ->patch('/citas/3/estado', ['estado' => 'no_asistio'])
            ->assertSessionHasErrors(['estado' => 'La inasistencia se marca 15 minutos después de la hora de la cita.']);

        $this->assertSame('agendada', Cita::find(3)->estado);
    }

    public function test_marca_inasistencia_pasada_la_tolerancia_y_libera_el_horario(): void
    {
        $cita = $this->citaPasada(3, 20);

        $this->actingAs($this->usuario('valentina'))
            ->patch('/citas/3/estado', ['estado' => 'no_asistio'])
            ->assertSessionHasNoErrors();

        $this->assertSame('no_asistio', Cita::find(3)->estado);
        $this->assertTrue(AuditoriaLog::where('accion', 'marcar_inasistencia')->exists());

        $this->actingAs($this->usuario('valentina'))
            ->patch('/citas/3/estado', ['estado' => 'atendida'])
            ->assertSessionHasErrors('estado');

        Cita::create([
            'paciente_id' => 2, 'doctor_id' => $cita->doctor_id, 'box_id' => $cita->box_id,
            'hora_inicio' => $cita->hora_inicio, 'hora_fin' => $cita->hora_fin, 'estado' => 'atendida',
        ]);
        $this->assertSame(2, Cita::where('hora_inicio', $cita->hora_inicio)->where('box_id', $cita->box_id)->count());
    }

    public function test_inicio_y_agenda_avisan_citas_pendientes_de_cierre(): void
    {
        $cita = $this->citaPasada(3, 30);

        $this->actingAs($this->usuario('valentina'))->get('/')
            ->assertOk()
            ->assertSee('Citas pendientes de cierre (1)')
            ->assertSee('Ana Paredes');

        $this->actingAs($this->usuario('valentina'))->get('/agenda?fecha='.$cita->hora_inicio->toDateString())
            ->assertOk()
            ->assertSee('Pendiente de cierre')
            ->assertSee('No asistió');
    }

    public function test_dentista_no_marca_inasistencia_en_agenda_ajena(): void
    {
        $this->citaPasada(2, 30);

        $this->actingAs($this->usuario('camila'))
            ->patch('/citas/2/estado', ['estado' => 'no_asistio'])
            ->assertForbidden();
    }

    public function test_reporte_por_doctor_cuenta_inasistencias(): void
    {
        $cita = $this->citaPasada(3, 30);
        Cita::where('id', 3)->update(['estado' => 'no_asistio']);

        $this->actingAs($this->usuario('admin'))
            ->get('/reportes?tipo=doctores&desde='.$cita->hora_inicio->toDateString().'&hasta='.$cita->hora_inicio->toDateString())
            ->assertOk()
            ->assertSee('Inasistencias');
    }
}
