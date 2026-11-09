<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PharData;
use RuntimeException;
use Symfony\Component\Process\Process;

class RespaldoService
{
    public const TABLAS_CONTROL = ['pacientes', 'fichas_clinicas', 'citas', 'presupuestos', 'pagos', 'auditoria_logs'];

    public function crear(): array
    {
        $carpeta = config('respaldos.carpeta');
        File::ensureDirectoryExists($carpeta);
        $temporal = $carpeta.DIRECTORY_SEPARATOR.'tmp-'.uniqid();
        File::ensureDirectoryExists($temporal);

        try {
            $volcado = $temporal.DIRECTORY_SEPARATOR.'base.dump';
            $this->ejecutar('pg_dump', ['-F', 'c', '--no-owner', '-f', $volcado, $this->conexion()['database']]);

            $manifiesto = [
                'creado' => now()->format('Y-m-d H:i:s'),
                'base' => $this->conexion()['database'],
                'conteos' => $this->conteos(DB::connection()),
                'radiografias' => count(Storage::disk('local')->allFiles('radiografias')),
            ];

            $tar = new PharData($temporal.DIRECTORY_SEPARATOR.'respaldo.tar');
            $tar->addFile($volcado, 'base.dump');
            foreach (Storage::disk('local')->allFiles('radiografias') as $archivo) {
                $tar->addFile(Storage::disk('local')->path($archivo), $archivo);
            }
            $tar->addFromString('manifiesto.json', json_encode($manifiesto, JSON_PRETTY_PRINT));

            $destino = $carpeta.DIRECTORY_SEPARATOR.'respaldo-'.now()->format('Ymd-His').'.enc';
            File::put($destino, Crypt::encryptString(File::get($temporal.DIRECTORY_SEPARATOR.'respaldo.tar')));

            $this->rotar();

            return ['archivo' => $destino, 'tamano' => File::size($destino), 'manifiesto' => $manifiesto];
        } finally {
            File::deleteDirectory($temporal);
        }
    }

    public function restaurar(string $archivo, string $baseDestino): array
    {
        if ($baseDestino === $this->conexion()['database']) {
            throw new RuntimeException('La prueba de restauración no puede usar la base de datos en uso.');
        }

        $carpeta = config('respaldos.carpeta');
        $temporal = $carpeta.DIRECTORY_SEPARATOR.'restaurar-'.uniqid();
        File::ensureDirectoryExists($temporal);

        try {
            File::put($temporal.DIRECTORY_SEPARATOR.'respaldo.tar', Crypt::decryptString(File::get($archivo)));
            (new PharData($temporal.DIRECTORY_SEPARATOR.'respaldo.tar'))->extractTo($temporal.DIRECTORY_SEPARATOR.'contenido');

            $contenido = $temporal.DIRECTORY_SEPARATOR.'contenido';
            $manifiesto = json_decode(File::get($contenido.DIRECTORY_SEPARATOR.'manifiesto.json'), true);

            $this->crearBaseSiNoExiste($baseDestino);
            $this->ejecutar('pg_restore', ['--clean', '--if-exists', '--no-owner', '-d', $baseDestino, $contenido.DIRECTORY_SEPARATOR.'base.dump']);

            config(['database.connections.restauracion' => array_merge($this->conexion(), ['database' => $baseDestino])]);
            DB::purge('restauracion');
            $restaurados = $this->conteos(DB::connection('restauracion'));
            DB::disconnect('restauracion');

            $radiografias = File::isDirectory($contenido.DIRECTORY_SEPARATOR.'radiografias')
                ? count(File::allFiles($contenido.DIRECTORY_SEPARATOR.'radiografias'))
                : 0;

            $comparacion = [];
            foreach ($manifiesto['conteos'] as $tabla => $esperado) {
                $comparacion[$tabla] = ['respaldo' => $esperado, 'restaurado' => $restaurados[$tabla] ?? 0];
            }
            $comparacion['radiografias (archivos)'] = ['respaldo' => $manifiesto['radiografias'], 'restaurado' => $radiografias];

            return [
                'manifiesto' => $manifiesto,
                'comparacion' => $comparacion,
                'correcta' => collect($comparacion)->every(fn ($fila) => $fila['respaldo'] === $fila['restaurado']),
            ];
        } finally {
            File::deleteDirectory($temporal);
        }
    }

    public function ultimo(): ?string
    {
        $archivos = glob(config('respaldos.carpeta').DIRECTORY_SEPARATOR.'respaldo-*.enc') ?: [];
        sort($archivos);

        return $archivos === [] ? null : end($archivos);
    }

    public function eliminarBase(string $base): void
    {
        if ($base === $this->conexion()['database']) {
            return;
        }

        DB::statement('DROP DATABASE IF EXISTS "'.str_replace('"', '', $base).'" WITH (FORCE)');
    }

    private function conteos($conexion): array
    {
        $conteos = [];
        foreach (self::TABLAS_CONTROL as $tabla) {
            $conteos[$tabla] = (int) $conexion->table($tabla)->count();
        }

        return $conteos;
    }

    private function crearBaseSiNoExiste(string $base): void
    {
        $existe = DB::selectOne('SELECT 1 AS existe FROM pg_database WHERE datname = ?', [$base]);
        if (! $existe) {
            DB::statement('CREATE DATABASE "'.str_replace('"', '', $base).'"');
        }
    }

    private function rotar(): void
    {
        $archivos = glob(config('respaldos.carpeta').DIRECTORY_SEPARATOR.'respaldo-*.enc') ?: [];
        rsort($archivos);

        foreach (array_slice($archivos, config('respaldos.conservar')) as $antiguo) {
            File::delete($antiguo);
        }
    }

    private function conexion(): array
    {
        return config('database.connections.'.config('database.default'));
    }

    private function ejecutar(string $programa, array $argumentos): void
    {
        $bin = config('respaldos.pg_bin');
        $ejecutable = $bin !== '' ? rtrim($bin, '\\/').DIRECTORY_SEPARATOR.$programa : $programa;
        $conexion = $this->conexion();

        $proceso = new Process(
            array_merge([$ejecutable, '-h', $conexion['host'], '-p', (string) $conexion['port'], '-U', $conexion['username']], $argumentos),
            null,
            ['PGPASSWORD' => (string) $conexion['password']],
        );
        $proceso->setTimeout(600);
        $proceso->run();

        if (! $proceso->isSuccessful()) {
            throw new RuntimeException("{$programa} falló: ".trim($proceso->getErrorOutput() ?: $proceso->getOutput()));
        }
    }
}
