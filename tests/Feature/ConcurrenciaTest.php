<?php

namespace Tests\Feature;

use App\Models\Cita;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ConcurrenciaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
    }

    protected function tearDown(): void
    {
        Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
        parent::tearDown();
    }

    public function test_diez_reservas_simultaneas_del_mismo_horario_aceptan_solo_una(): void
    {
        $fecha = Cita::find(1)->hora_inicio->toDateString();
        $inicio = microtime(true) + 6;

        $procesos = [];
        for ($i = 0; $i < 10; $i++) {
            $proceso = new Process(
                [PHP_BINARY, 'artisan', 'agenda:intento-reserva', '2', '1', '1', $fecha, '16:00', '--inicio='.$inicio],
                base_path(),
                ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DB_DATABASE' => 'clinica_diente_feliz_test'],
            );
            $proceso->setTimeout(60);
            $proceso->start();
            $procesos[] = $proceso;
        }

        $resultados = [];
        foreach ($procesos as $proceso) {
            $proceso->wait();
            $resultados[] = trim($proceso->getOutput()).trim($proceso->getErrorOutput());
        }

        $aceptadas = array_filter($resultados, fn ($r) => str_starts_with($r, 'aceptada'));
        $rechazadas = array_filter($resultados, fn ($r) => str_starts_with($r, 'rechazada'));

        $this->assertCount(1, $aceptadas, implode("\n", $resultados));
        $this->assertCount(9, $rechazadas, implode("\n", $resultados));
        $this->assertSame(1, Cita::where('doctor_id', 1)->where('hora_inicio', $fecha.' 16:00:00')->count());
    }
}
