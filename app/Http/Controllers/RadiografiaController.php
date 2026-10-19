<?php

namespace App\Http\Controllers;

use App\Http\Requests\RadiografiaRequest;
use App\Models\Paciente;
use App\Models\Radiografia;
use App\Services\AuditoriaService;
use Illuminate\Support\Facades\Storage;

class RadiografiaController extends Controller
{
    public function store(RadiografiaRequest $request, Paciente $paciente)
    {
        $ficha = $paciente->ficha;

        $ruta = $request->file('archivo')->store("radiografias/{$ficha->id}", 'local');

        $radiografia = Radiografia::create([
            'ficha_id' => $ficha->id,
            'ruta_archivo' => $ruta,
            'fecha' => $request->validated('fecha_radiografia'),
            'diagnostico' => $request->validated('diagnostico_radiografia'),
        ]);
        AuditoriaService::creado($radiografia);

        return redirect()->route('fichas.show', $paciente)->with('exito', 'Radiografía agregada.');
    }

    public function archivo(Radiografia $radiografia)
    {
        abort_unless(Storage::disk('local')->exists($radiografia->ruta_archivo), 404);

        AuditoriaService::registrar('ver', 'radiografias', $radiografia->id);

        return Storage::disk('local')->response($radiografia->ruta_archivo, null, [
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
