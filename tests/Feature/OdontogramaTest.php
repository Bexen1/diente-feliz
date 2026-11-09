<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OdontogramaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function dentista(): Usuario
    {
        return Usuario::where('email', 'camila@dientefeliz.test')->first();
    }

    public function test_cada_pieza_dibuja_sus_cinco_caras(): void
    {
        $html = $this->actingAs($this->dentista())->get('/pacientes/1/ficha')->assertOk()->getContent();

        $this->assertGreaterThanOrEqual(52, substr_count($html, 'class="diente-svg"'));
        $this->assertStringContainsString('fill="#ef4444"', $html);
        $this->assertStringContainsString('fill="#3b82f6"', $html);
        $this->assertStringContainsString('stroke="#6b7280"', $html);
    }

    public function test_cara_mesial_cambia_de_lado_segun_el_cuadrante(): void
    {
        $vista = fn (int $pieza) => view('partials.diente', ['pieza' => $pieza, 'hallazgo' => ['estado' => 'caries', 'caras' => ['mesial']]])->render();

        $this->assertStringContainsString('<polygon points="30,0 30,30 21,21 21,9" fill="#ef4444"/>', $vista(16));
        $this->assertStringContainsString('<polygon points="0,0 9,9 9,21 0,30" fill="#ef4444"/>', $vista(26));
    }

    public function test_historial_registra_cambios_y_filtra_por_pieza(): void
    {
        $this->actingAs($this->dentista())->put('/pacientes/1/ficha/odontograma', ['pieza' => 14, 'estado' => 'caries', 'caras' => ['oclusal']]);
        $this->actingAs($this->dentista())->put('/pacientes/1/ficha/odontograma', ['pieza' => 14, 'estado' => 'restauracion', 'caras' => ['oclusal']]);
        $this->actingAs($this->dentista())->put('/pacientes/1/ficha/odontograma', ['pieza' => 21, 'estado' => 'ausente']);

        $todas = $this->actingAs($this->dentista())->get('/pacientes/1/ficha')->assertOk()->getContent();
        $this->assertStringContainsString('Historial del odontograma', $todas);
        $this->assertSame(3, substr_count($todas, 'Dra. Camila Rojas</td>'));

        $pieza14 = $this->actingAs($this->dentista())->get('/pacientes/1/ficha?pieza=14')->assertOk()->getContent();
        $this->assertSame(2, substr_count($pieza14, 'Dra. Camila Rojas</td>'));
        $this->assertStringContainsString('Caries <i class="bi bi-arrow-right"></i> <strong>Restauración</strong>', $pieza14);
    }

    public function test_pieza_inexistente_en_el_filtro_se_rechaza(): void
    {
        $this->actingAs($this->dentista())->get('/pacientes/1/ficha?pieza=99')->assertSessionHasErrors('pieza');
    }
}
