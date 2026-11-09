<?php

namespace App\Http\Controllers;

use App\Http\Requests\HorarioRequest;
use App\Models\Doctor;
use App\Models\Horario;
use App\Services\AuditoriaService;
use Illuminate\Support\Facades\DB;

class HorarioController extends Controller
{
    public function store(HorarioRequest $request, Doctor $doctor)
    {
        $dias = collect($request->validated('dias'))->map(fn ($dia) => (int) $dia)->sort()->values();

        DB::transaction(function () use ($doctor, $dias, $request) {
            foreach ($dias as $dia) {
                $horario = $doctor->horarios()->create([
                    'dia_semana' => $dia,
                    'hora_inicio' => $request->validated('hora_inicio'),
                    'hora_fin' => $request->validated('hora_fin'),
                ]);
                AuditoriaService::creado($horario);
            }
        });

        $mensaje = $dias->count() === 1 ? 'Bloque de horario agregado.' : "Bloque de horario agregado en {$dias->count()} días.";

        return redirect()->route('doctores.edit', $doctor)->with('exito', $mensaje);
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
