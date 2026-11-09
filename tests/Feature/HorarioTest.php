<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HorarioTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function admin(): Usuario
    {
        return Usuario::where('email', 'admin@dientefeliz.test')->first();
    }

    public function test_un_bloque_se_crea_para_varios_dias_en_un_paso(): void
    {
        $doctor = Doctor::find(1);
        $doctor->horarios()->delete();

        $this->actingAs($this->admin())->post("/doctores/{$doctor->id}/horarios", [
            'dias' => [1, 2, 3, 4, 5], 'hora_inicio' => '09:00', 'hora_fin' => '13:00',
        ])->assertSessionHas('exito', 'Bloque de horario agregado en 5 días.');

        $this->assertSame([1, 2, 3, 4, 5], $doctor->horarios()->orderBy('dia_semana')->pluck('dia_semana')->all());
    }

    public function test_si_un_dia_se_cruza_no_se_crea_ninguno(): void
    {
        $doctor = Doctor::find(1);
        $antes = $doctor->horarios()->count();

        $this->actingAs($this->admin())->post("/doctores/{$doctor->id}/horarios", [
            'dias' => [5, 6], 'hora_inicio' => '17:00', 'hora_fin' => '19:00',
        ])->assertSessionHasErrors(['hora_inicio' => 'El bloque se cruza con otro bloque del Viernes.']);

        $this->assertSame($antes, $doctor->horarios()->count());
    }

    public function test_requiere_al_menos_un_dia(): void
    {
        $this->actingAs($this->admin())->post('/doctores/1/horarios', [
            'hora_inicio' => '09:00', 'hora_fin' => '13:00',
        ])->assertSessionHasErrors('dias');
    }
}
