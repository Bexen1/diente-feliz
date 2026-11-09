<?php

namespace App\Console\Commands;

use App\Services\RespaldoService;
use Illuminate\Console\Command;
use Throwable;

class RespaldoRestaurarCommand extends Command
{
    protected $signature = 'respaldo:restaurar
        {archivo? : Ruta del respaldo; por defecto, el más reciente}
        {--base= : Base de datos donde se restaura la prueba}';

    protected $description = 'Restaura un respaldo en una base aparte y compara los datos con el original';

    public function handle(RespaldoService $respaldos): int
    {
        $archivo = $this->argument('archivo') ?? $respaldos->ultimo();
        if (! $archivo || ! is_file($archivo)) {
            $this->error('No se encontró un respaldo para restaurar.');

            return self::FAILURE;
        }

        $base = $this->option('base') ?: config('respaldos.base_restauracion');

        try {
            $resultado = $respaldos->restaurar($archivo, $base);
        } catch (Throwable $e) {
            $this->error('La restauración falló: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Respaldo restaurado en la base \"{$base}\"");
        $this->line('Respaldo del '.$resultado['manifiesto']['creado'].' · '.basename($archivo));
        $this->table(
            ['Dato', 'En el respaldo', 'Restaurado', 'Resultado'],
            collect($resultado['comparacion'])->map(fn ($fila, $dato) => [
                $dato, $fila['respaldo'], $fila['restaurado'], $fila['respaldo'] === $fila['restaurado'] ? 'OK' : 'DIFERENCIA',
            ])->values()->all()
        );

        if (! $resultado['correcta']) {
            $this->error('La restauración no coincide con el respaldo.');

            return self::FAILURE;
        }

        $this->info('Prueba de restauración correcta: todos los datos coinciden.');

        return self::SUCCESS;
    }
}
