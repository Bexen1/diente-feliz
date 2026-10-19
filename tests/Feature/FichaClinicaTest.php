<?php

namespace Tests\Feature;

use App\Models\AuditoriaLog;
use App\Models\FichaClinica;
use App\Models\Radiografia;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FichaClinicaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private function usuario(string $nombre): Usuario
    {
        return Usuario::where('email', "$nombre@dientefeliz.test")->first();
    }

    public function test_recepcionista_no_accede_a_la_ficha(): void
    {
        $this->actingAs($this->usuario('valentina'))->get('/pacientes/1/ficha')->assertForbidden();
    }

    public function test_dentista_ve_la_ficha_con_52_piezas_y_queda_registrado(): void
    {
        $respuesta = $this->actingAs($this->usuario('camila'))->get('/pacientes/1/ficha')->assertOk();

        $this->assertSame(52, substr_count($respuesta->getContent(), 'data-pieza='));
        $this->assertTrue(AuditoriaLog::where('accion', 'ver')->where('tabla_afectada', 'fichas_clinicas')->exists());
    }

    public function test_hallazgo_actualiza_solo_esa_pieza_y_se_audita(): void
    {
        $this->actingAs($this->usuario('camila'))->put('/pacientes/1/ficha/odontograma', [
            'pieza' => 14, 'estado' => 'caries', 'caras' => ['mesial', 'oclusal'],
        ])->assertRedirect('/pacientes/1/ficha');

        $odontograma = FichaClinica::where('paciente_id', 1)->first()->estado_odontograma_json;
        $this->assertSame('caries', $odontograma[14]['estado']);
        $this->assertSame(['mesial', 'oclusal'], $odontograma[14]['caras']);
        $this->assertSame('caries', $odontograma[16]['estado'], 'Las demás piezas no cambian.');

        $log = AuditoriaLog::where('accion', 'actualizar_odontograma')->latest('id')->first();
        $this->assertSame('sano', $log->payload_cambios_json['antes']['14']['estado']);
        $this->assertSame('caries', $log->payload_cambios_json['despues']['14']['estado']);
    }

    public function test_pieza_inexistente_se_rechaza(): void
    {
        $this->actingAs($this->usuario('camila'))
            ->put('/pacientes/1/ficha/odontograma', ['pieza' => 19, 'estado' => 'caries'])
            ->assertSessionHasErrors('pieza');
    }

    public function test_registra_evolucion_firmada_por_el_dentista(): void
    {
        $this->actingAs($this->usuario('camila'))->post('/pacientes/1/ficha/evoluciones', [
            'fecha' => now()->toDateString(), 'diagnostico' => 'Caries en pieza 14', 'tratamiento_realizado' => 'Resina',
        ])->assertRedirect();

        $evolucion = FichaClinica::where('paciente_id', 1)->first()->evoluciones()->first();
        $this->assertSame($this->usuario('camila')->doctor->id, $evolucion->doctor_id);
    }

    public function test_radiografia_valida_se_guarda_en_disco_privado(): void
    {
        Storage::fake('local');

        $this->actingAs($this->usuario('camila'))->post('/pacientes/1/ficha/radiografias', [
            'archivo' => UploadedFile::fake()->createWithContent('panoramica.png', base64_decode(self::PNG)),
            'fecha_radiografia' => now()->toDateString(),
            'diagnostico_radiografia' => 'Sin hallazgos',
        ])->assertSessionHasNoErrors();

        $radiografia = Radiografia::first();
        Storage::disk('local')->assertExists($radiografia->ruta_archivo);
        $this->assertStringNotContainsString('panoramica', $radiografia->ruta_archivo, 'El nombre lo genera el sistema.');

        $this->actingAs($this->usuario('camila'))->get(route('radiografias.archivo', $radiografia))->assertOk();
        $this->actingAs($this->usuario('valentina'))->get(route('radiografias.archivo', $radiografia))->assertForbidden();
    }

    public function test_ejecutable_renombrado_como_imagen_se_rechaza(): void
    {
        Storage::fake('local');

        $ruta = tempnam(sys_get_temp_dir(), 'exe');
        file_put_contents($ruta, "MZ\x90\x00\x03\x00\x00\x00\x04\x00 This program cannot be run in DOS mode.");
        $archivo = new UploadedFile($ruta, 'radiografia.jpg', null, null, true);

        $this->actingAs($this->usuario('camila'))->post('/pacientes/1/ficha/radiografias', [
            'archivo' => $archivo,
            'fecha_radiografia' => now()->toDateString(),
        ])->assertSessionHasErrors('archivo');

        $this->assertSame(0, Radiografia::count());
    }
}
