<?php

namespace App\Http\Controllers;

use App\Http\Requests\HorarioRequest;
use App\Models\Doctor;
use App\Models\Horario;
use App\Services\AuditoriaService;

class HorarioController extends Controller
{
    public function store(HorarioRequest $request, Doctor $doctor)
    {
        $horario = $doctor->horarios()->create($request->validated());
        AuditoriaService::creado($horario);

        return redirect()->route('doctores.edit', $doctor)->with('exito', 'Bloque de horario agregado.');
    }

    public function destroy(Doctor $doctor, Horario $horario)
    {
        abort_unless($horario->doctor_id === $doctor->id, 404);

        $antes = $horario->getAttributes();
        $horario->delete();
        AuditoriaService::registrar('eliminar', 'horarios', $horario->id, $antes, null);

        return redirect()->route('doctores.edit', $doctor)->with('exito', 'Bloque de horario eliminado.');
    }
}
