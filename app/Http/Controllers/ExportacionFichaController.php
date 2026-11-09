<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use App\Services\AuditoriaService;
use App\Services\ExportacionFichaService;

class ExportacionFichaController extends Controller
{
    public function __construct(private ExportacionFichaService $exportacion) {}

    public function imprimir(Paciente $paciente)
    {
        $datos = $this->exportacion->datos($paciente);
        AuditoriaService::registrar('exportar', 'pacientes', $paciente->id, null, ['formato' => 'imprimible']);

        return view('fichas.exportar', ['paciente' => $paciente, 'datos' => $datos]);
    }

    public function descargar(Paciente $paciente)
    {
        $datos = $this->exportacion->datos($paciente);
        AuditoriaService::registrar('exportar', 'pacientes', $paciente->id, null, ['formato' => 'json']);

        $nombre = 'ficha-'.$paciente->rut.'-'.now()->format('Ymd').'.json';

        return response()->json($datos, 200, [
            'Content-Disposition' => 'attachment; filename="'.$nombre.'"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
