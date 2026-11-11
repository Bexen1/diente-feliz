<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaRutTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private string $dia;

    protected function setUp(): void
    {
        parent::setUp();
        $dia = Carbon::today()->addDays(7);
        while ($dia->isWeekend()) {
            $dia->addDay();
        }
        $this->dia = $dia->toDateString();
    }

    private function agendar(string $rut)
    {
        return $this->actingAs(Usuario::where('email', 'valentina@dientefeliz.test')->first())->post('/citas', [
            'rut' => $rut, 'doctor_id' => 1, 'box_id' => 1, 'fecha' => $this->dia, 'hora' => '11:00', 'duracion' => 30,
        ]);
    }

    public function test_agenda_con_el_rut_aunque_tenga_puntos_y_k_minuscula(): void
    {
        $paciente = Paciente::find(2);
        [$numero, $dv] = explode('-', $paciente->rut);
        $conPuntos = number_format((int) $numero, 0, ',', '.').'-'.strtolower($dv);

        $this->agendar($conPuntos)->assertSessionHasNoErrors();

        $this->assertTrue(Cita::where('paciente_id', 2)->whereDate('hora_inicio', $this->dia)->exists());
    }

    public function test_rechaza_un_rut_que_no_es_paciente(): void
    {
        $this->agendar('11111111-1')->assertSessionHasErrors(['rut' => 'No hay un paciente activo con ese RUT.']);
    }

    public function test_rechaza_un_rut_invalido(): void
    {
        $this->agendar('12345678-9')->assertSessionHasErrors('rut');
    }

    public function test_la_agenda_ofrece_los_rut_y_los_muestra_en_la_lista(): void
    {
        $paciente = Paciente::find(1);

        $this->actingAs(Usuario::where('email', 'valentina@dientefeliz.test')->first())
            ->get('/agenda?rut='.$paciente->rut)
            ->assertSee('name="rut"', false)
            ->assertSee('value="'.$paciente->rut.'"', false)
            ->assertSee('<datalist id="pacientes-rut">', false);
    }
}
