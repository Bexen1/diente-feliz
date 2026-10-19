<?php

namespace Database\Seeders;

use App\Models\Box;
use App\Models\Cita;
use App\Models\Convenio;
use App\Models\Doctor;
use App\Models\FichaClinica;
use App\Models\Paciente;
use App\Models\Pago;
use App\Models\Presupuesto;
use App\Models\Tratamiento;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PacienteSeeder extends Seeder
{
    public function run(): void
    {
        $convenio = Convenio::first();

        $pacientes = [
            ['12345678-5', 'María Contreras', '+56911111111', $convenio->id, 'Alergia a la penicilina'],
            ['11222333-9', 'Jorge Pino', '+56922222222', null, null],
            ['15666777-3', 'Ana Paredes', '+56933333333', null, null],
            ['17888999-0', 'Luis Vera', '+56944444444', $convenio->id, null],
        ];

        foreach ($pacientes as [$rut, $nombre, $telefono, $convenioId, $alergias]) {
            $paciente = Paciente::create([
                'rut' => $rut,
                'nombre' => $nombre,
                'telefono' => $telefono,
                'convenio_id' => $convenioId,
                'alergias' => $alergias,
            ]);

            FichaClinica::create(['paciente_id' => $paciente->id]);
        }

        FichaClinica::where('paciente_id', 1)->update(['estado_odontograma_json' => json_encode([
            '16' => ['estado' => 'caries', 'caras' => ['oclusal', 'mesial'], 'fecha' => '2026-10-10'],
            '26' => ['estado' => 'restauracion', 'caras' => ['oclusal'], 'fecha' => '2026-09-02'],
            '36' => ['estado' => 'ausente', 'caras' => [], 'fecha' => '2026-08-15'],
        ])]);

        $dia = Carbon::tomorrow();
        while ($dia->isWeekend()) {
            $dia->addDay();
        }

        [$camila, $matias] = Doctor::orderBy('id')->get();
        [$box1, $box2] = Box::orderBy('id')->get();

        $citas = [
            [1, $camila, $box1, '09:00', 45],
            [2, $matias, $box2, '09:30', 30],
            [3, $camila, $box1, '10:00', 30],
            [4, $matias, $box1, '11:00', 60],
        ];

        foreach ($citas as [$pacienteId, $doctor, $box, $hora, $minutos]) {
            $inicio = $dia->copy()->setTimeFromTimeString($hora);
            Cita::create([
                'paciente_id' => $pacienteId,
                'doctor_id' => $doctor->id,
                'box_id' => $box->id,
                'hora_inicio' => $inicio,
                'hora_fin' => $inicio->copy()->addMinutes($minutos),
            ]);
        }

        $presupuesto = Presupuesto::create([
            'paciente_id' => 1,
            'doctor_id' => $camila->id,
            'estado' => 'aceptado',
            'total_calculado' => 102000,
        ]);

        $resina = Tratamiento::where('nombre', 'Restauración con resina')->first();
        $extraccion = Tratamiento::where('nombre', 'Extracción simple')->first();

        $presupuesto->detalles()->create(['tratamiento_id' => $resina->id, 'cantidad' => 2, 'subtotal' => 90000]);
        $presupuesto->detalles()->create(['tratamiento_id' => $extraccion->id, 'cantidad' => 1, 'subtotal' => 30000]);

        Pago::create(['presupuesto_id' => $presupuesto->id, 'monto' => 40000, 'fecha' => '2026-10-12']);
        Pago::create(['presupuesto_id' => $presupuesto->id, 'monto' => 20000, 'fecha' => '2026-10-15']);
    }
}
