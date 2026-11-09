<?php

namespace App\Console\Commands;

use App\Services\RespaldoService;
use Illuminate\Console\Command;
use Throwable;

class RespaldoCrearCommand extends Command
{
    protected $signature = 'respaldo:crear';

    protected $description = 'Crea un respaldo cifrado de la base de datos y de las radiografías';

    public function handle(RespaldoService $respaldos): int
    {
        try {
            $resultado = $respaldos->crear();
        } catch (Throwable $e) {
            $this->error('No se pudo crear el respaldo: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Respaldo cifrado creado: '.$resultado['archivo']);
        $this->line('Tamaño: '.number_format($resultado['tamano'] / 1024, 1, ',', '.').' KB');
        $this->table(
            ['Tabla', 'Registros'],
            collect($resultado['manifiesto']['conteos'])->map(fn ($total, $tabla) => [$tabla, $total])->values()->all()
        );
        $this->line('Radiografías incluidas: '.$resultado['manifiesto']['radiografias']);

        return self::SUCCESS;
    }
}
