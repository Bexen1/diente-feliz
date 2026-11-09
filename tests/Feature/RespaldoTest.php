<?php

namespace Tests\Feature;

use App\Services\RespaldoService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class RespaldoTest extends TestCase
{
    private const BASE_PRUEBA = 'clinica_diente_feliz_respaldo_test';

    private string $carpeta;

    protected function setUp(): void
    {
        parent::setUp();

        $bin = config('respaldos.pg_bin');
        $pgDump = new Process([$bin !== '' ? rtrim($bin, '\\/').DIRECTORY_SEPARATOR.'pg_dump' : 'pg_dump', '--version']);
        $pgDump->run();
        if (! $pgDump->isSuccessful()) {
            $this->markTestSkipped('pg_dump no está disponible en este equipo (configure PG_BIN).');
        }

        Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);

        $this->carpeta = storage_path('framework/testing/respaldos');
        File::deleteDirectory($this->carpeta);
        config(['respaldos.carpeta' => $this->carpeta]);
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        if (isset($this->carpeta)) {
            app(RespaldoService::class)->eliminarBase(self::BASE_PRUEBA);
            File::deleteDirectory($this->carpeta);
            Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
        }

        parent::tearDown();
    }

    public function test_respaldo_cifrado_se_restaura_completo_en_otra_base(): void
    {
        Storage::disk('local')->put('radiografias/1/panoramica.png', 'imagen de prueba');

        $resultado = app(RespaldoService::class)->crear();

        $this->assertFileExists($resultado['archivo']);
        $contenido = File::get($resultado['archivo']);
        $this->assertStringNotContainsString('pacientes', $contenido);
        $this->assertStringNotContainsString('PGDMP', $contenido);
        $this->assertSame(4, $resultado['manifiesto']['conteos']['pacientes']);
        $this->assertSame(1, $resultado['manifiesto']['radiografias']);

        $restauracion = app(RespaldoService::class)->restaurar($resultado['archivo'], self::BASE_PRUEBA);

        $this->assertTrue($restauracion['correcta']);
        $this->assertSame(4, $restauracion['comparacion']['pacientes']['restaurado']);
        $this->assertSame(1, $restauracion['comparacion']['radiografias (archivos)']['restaurado']);
    }

    public function test_no_restaura_sobre_la_base_en_uso(): void
    {
        $resultado = app(RespaldoService::class)->crear();

        $this->expectException(RuntimeException::class);
        app(RespaldoService::class)->restaurar($resultado['archivo'], config('database.connections.pgsql.database'));
    }

    public function test_conserva_solo_los_respaldos_mas_recientes(): void
    {
        config(['respaldos.conservar' => 2]);
        File::ensureDirectoryExists($this->carpeta);
        File::put($this->carpeta.'/respaldo-20200101-000000.enc', 'antiguo');
        File::put($this->carpeta.'/respaldo-20210101-000000.enc', 'antiguo');

        app(RespaldoService::class)->crear();

        $archivos = array_map('basename', glob($this->carpeta.'/respaldo-*.enc'));
        $this->assertCount(2, $archivos);
        $this->assertNotContains('respaldo-20200101-000000.enc', $archivos);
    }

    public function test_comandos_de_consola(): void
    {
        $this->artisan('respaldo:crear')->assertSuccessful();
        $this->artisan('respaldo:restaurar', ['--base' => self::BASE_PRUEBA])
            ->expectsOutputToContain('Prueba de restauración correcta')
            ->assertSuccessful();
    }
}
