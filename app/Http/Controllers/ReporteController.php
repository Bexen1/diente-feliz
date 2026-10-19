<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReporteRequest;
use App\Services\AuditoriaService;
use App\Services\ReporteService;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    public function index(ReporteRequest $request, ReporteService $reportes)
    {
        $tipo = $request->validated('tipo') ?? 'doctores';
        $desde = Carbon::parse($request->validated('desde') ?? now()->startOfMonth());
        $hasta = Carbon::parse($request->validated('hasta') ?? now()->endOfMonth());
        $formato = $request->validated('formato') ?? 'pantalla';

        $reporte = $reportes->generar($tipo, $desde, $hasta);

        if ($formato !== 'pantalla') {
            AuditoriaService::registrar('exportar_reporte', 'reportes', null, null, [
                'tipo' => $tipo, 'formato' => $formato, 'desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString(),
            ]);
        }

        if ($formato === 'csv') {
            return $this->csv($reporte, "reporte-{$tipo}-{$desde->format('Ymd')}-{$hasta->format('Ymd')}.csv");
        }

        return view($formato === 'imprimir' ? 'reportes.imprimir' : 'reportes.index', [
            'reporte' => $reporte,
            'tipos' => ReporteService::TIPOS,
            'tipo' => $tipo,
            'desde' => $desde,
            'hasta' => $hasta,
        ]);
    }

    private function csv(array $reporte, string $nombreArchivo): StreamedResponse
    {
        return response()->streamDownload(function () use ($reporte) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, $reporte['columnas'], ';');
            foreach ($reporte['filas'] as $fila) {
                fputcsv($salida, array_map(fn ($valor) => $this->celdaSegura($valor), $fila), ';');
            }
            fclose($salida);
        }, $nombreArchivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function celdaSegura(mixed $valor): mixed
    {
        if (is_string($valor) && $valor !== '' && in_array($valor[0], ['=', '+', '-', '@'], true)) {
            return "'".$valor;
        }

        return $valor;
    }
}
