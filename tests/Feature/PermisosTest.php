<?php

namespace Tests\Feature;

use App\Models\AuditoriaLog;
use App\Models\Paciente;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermisosTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function usuario(string $nombre): Usuario
    {
        return Usuario::where('email', "$nombre@dientefeliz.test")->first();
    }

    public function test_recepcionista_no_puede_abrir_usuarios_y_queda_auditado(): void
    {
        $this->actingAs($this->usuario('valentina'))->get('/usuarios')->assertForbidden();

        $this->assertTrue(AuditoriaLog::where('accion', 'acceso_denegado')->exists());
    }

    public function test_administrador_hereda_y_entra_a_todo(): void
    {
        $admin = $this->usuario('admin');

        foreach (['/usuarios', '/doctores', '/sucursales', '/box', '/convenios', '/tratamientos', '/pacientes', '/pacientes/1'] as $ruta) {
            $this->actingAs($admin)->get($ruta)->assertOk();
        }
    }

    public function test_dentista_y_recepcionista_leen_sucursales_pero_no_las_editan(): void
    {
        foreach (['camila', 'valentina'] as $nombre) {
            $this->actingAs($this->usuario($nombre))->get('/sucursales')->assertOk();
            $this->actingAs($this->usuario($nombre))->get('/sucursales/create')->assertForbidden();
        }
    }

    public function test_recepcionista_no_ve_ni_puede_escribir_antecedentes_de_salud(): void
    {
        $recepcionista = $this->usuario('valentina');

        $this->actingAs($recepcionista)->get('/pacientes/1')
            ->assertOk()
            ->assertDontSee('Alergia a la penicilina');

        $this->actingAs($recepcionista)->put('/pacientes/1', [
            'rut' => '12.345.678-5', 'nombre' => 'María Contreras', 'telefono' => '+56911111111',
            'alergias' => 'Cambiado por recepción',
        ])->assertRedirect('/pacientes/1');

        $this->assertSame('Alergia a la penicilina', Paciente::find(1)->alergias);
    }

    public function test_dentista_registra_antecedentes_cifrados(): void
    {
        $this->actingAs($this->usuario('camila'))->post('/pacientes', [
            'rut' => '11.111.111-1', 'nombre' => 'Paciente Prueba', 'telefono' => '+56900000000',
            'alergias' => 'Látex',
        ])->assertRedirect();

        $paciente = Paciente::where('rut', '11111111-1')->first();
        $this->assertSame('Látex', $paciente->alergias);
        $this->assertNotSame('Látex', $paciente->getRawOriginal('alergias'));
        $this->assertNotNull($paciente->ficha, 'Al crear el paciente se crea su ficha.');
    }

    public function test_rut_con_digito_verificador_incorrecto_se_rechaza(): void
    {
        $this->actingAs($this->usuario('valentina'))
            ->post('/pacientes', ['rut' => '12.345.678-9', 'nombre' => 'X', 'telefono' => '1'])
            ->assertSessionHasErrors('rut');
    }

    public function test_perfil_ignora_rol_id(): void
    {
        $recepcionista = $this->usuario('valentina');
        $rolOriginal = $recepcionista->rol_id;

        $this->actingAs($recepcionista)->put('/perfil', [
            'password_actual' => 'DienteFeliz2026',
            'password' => 'OtraClaveSegura#2026',
            'password_confirmation' => 'OtraClaveSegura#2026',
            'rol_id' => 1,
        ]);

        $this->assertSame($rolOriginal, $recepcionista->fresh()->rol_id);
    }
}
