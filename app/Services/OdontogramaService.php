<?php

namespace App\Services;

use App\Models\FichaClinica;
use Illuminate\Support\Facades\DB;

class OdontogramaService
{
    public const FILAS = [
        'Permanentes superiores' => [[18, 17, 16, 15, 14, 13, 12, 11], [21, 22, 23, 24, 25, 26, 27, 28]],
        'Temporales superiores' => [[55, 54, 53, 52, 51], [61, 62, 63, 64, 65]],
        'Temporales inferiores' => [[85, 84, 83, 82, 81], [71, 72, 73, 74, 75]],
        'Permanentes inferiores' => [[48, 47, 46, 45, 44, 43, 42, 41], [31, 32, 33, 34, 35, 36, 37, 38]],
    ];

    public const ESTADOS = [
        'sano' => ['', 'Sano'],
        'caries' => ['C', 'Caries'],
        'restauracion' => ['R', 'Restauración'],
        'endodoncia' => ['E', 'Endodoncia'],
        'extraccion' => ['X', 'Extracción indicada'],
        'ausente' => ['A', 'Ausente'],
    ];

    public const CARAS = ['vestibular', 'lingual', 'mesial', 'distal', 'oclusal'];

    public static function piezas(): array
    {
        return array_merge(...array_merge(...array_values(self::FILAS)));
    }

    public function registrarHallazgo(FichaClinica $ficha, int $pieza, string $estado, array $caras): void
    {
        DB::transaction(function () use ($ficha, $pieza, $estado, $caras) {
            $ficha = FichaClinica::lockForUpdate()->findOrFail($ficha->id);

            $odontograma = $ficha->estado_odontograma_json ?? [];
            $antes = $odontograma[$pieza] ?? ['estado' => 'sano'];

            if ($estado === 'sano') {
                unset($odontograma[$pieza]);
                $despues = ['estado' => 'sano'];
            } else {
                $despues = [
                    'estado' => $estado,
                    'caras' => array_values(array_intersect(self::CARAS, $caras)),
                    'fecha' => now()->toDateString(),
                ];
                $odontograma[$pieza] = $despues;
            }

            ksort($odontograma);
            $ficha->update(['estado_odontograma_json' => $odontograma]);

            AuditoriaService::registrar('actualizar_odontograma', 'fichas_clinicas', $ficha->id,
                [(string) $pieza => $antes], [(string) $pieza => $despues]);
        });
    }
}
