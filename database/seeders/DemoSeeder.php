<?php

namespace Database\Seeders;

use App\Models\Box;
use App\Models\Cita;
use App\Models\Convenio;
use App\Models\Doctor;
use App\Models\FichaClinica;
use App\Models\Paciente;
use App\Models\Presupuesto;
use App\Models\Sucursal;
use App\Models\Tratamiento;
use App\Rules\Rut;
use App\Services\PresupuestoService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    private const NOMBRES = [
        'Catalina', 'Benjamín', 'Javiera', 'Vicente', 'Fernanda', 'Martín', 'Constanza', 'Tomás', 'Valentina', 'Agustín',
        'Isidora', 'Joaquín', 'Antonia', 'Matías', 'Florencia', 'Diego', 'Josefa', 'Cristóbal', 'Trinidad', 'Ignacio',
    ];

    private const APELLIDOS = [
        'González', 'Muñoz', 'Rojas', 'Díaz', 'Pérez', 'Soto', 'Contreras', 'Silva', 'Martínez', 'Sepúlveda',
        'Morales', 'Rodríguez', 'López', 'Fuentes', 'Hernández', 'Torres', 'Araya', 'Flores', 'Espinoza', 'Valenzuela',
    ];

    private array $ocupado = [];

    public function run(PresupuestoService $presupuestos): void
    {
        mt_srand(2026);
        $hoy = Carbon::today();

        $this->crearCatalogo();
        $boxes = $this->crearSucursalCentro();
        $pacientes = $this->crearPacientes($hoy);

        foreach (Cita::all() as $cita) {
            $this->marcar($cita->doctor_id, $cita->box_id, $cita->hora_inicio, $cita->hora_fin);
        }

        $this->crearCitas($hoy, $boxes, $pacientes);
        $this->crearPresupuestos($presupuestos, $hoy);
    }

    private function crearCatalogo(): void
    {
        $tratamientos = [
            ['Destartraje', 35000],
            ['Sellante de fosas y fisuras', 15000],
            ['Blanqueamiento dental', 150000],
            ['Corona de porcelana', 280000],
        ];

        foreach ($tratamientos as [$nombre, $costo]) {
            Tratamiento::firstOrCreate(['nombre' => $nombre], ['costo_base' => $costo]);
        }
    }

    private function crearSucursalCentro(): array
    {
        $centro = Sucursal::firstOrCreate(
            ['nombre' => 'Sucursal Centro'],
            ['direccion' => 'Calle Barros Arana 456, Concepción']
        );
        $box3 = Box::firstOrCreate(['nombre' => 'Box 3', 'sucursal_id' => $centro->id]);

        [$box1, $box2] = Box::where('sucursal_id', '<>', $centro->id)->orderBy('id')->take(2)->get();

        return [$box1, $box2, $box3];
    }

    private function crearPacientes(Carbon $hoy): array
    {
        $convenios = Convenio::orderBy('id')->pluck('id')->all();

        for ($i = 1; $i <= 40; $i++) {
            $numero = (string) (10000000 + $i * 271829);
            $rut = $numero.'-'.Rut::digitoVerificador($numero);

            if (Paciente::where('rut', $rut)->exists()) {
                continue;
            }

            $sorteo = mt_rand(1, 100);
            $convenioId = $sorteo <= 55 ? null : ($sorteo <= 80 ? $convenios[0] : ($convenios[1] ?? $convenios[0]));

            $registro = $i <= 15
                ? $hoy->copy()->subMonths(mt_rand(7, 12))->subDays(mt_rand(0, 27))
                : $hoy->copy()->subDays(mt_rand(0, 180));

            $paciente = new Paciente([
                'rut' => $rut,
                'nombre' => self::NOMBRES[$i % 20].' '.self::APELLIDOS[($i * 7) % 20].' '.self::APELLIDOS[($i * 13 + 3) % 20],
                'fecha_nacimiento' => $hoy->copy()->subYears(mt_rand(6, 75))->subDays(mt_rand(0, 364)),
                'telefono' => '+569'.mt_rand(10000000, 99999999),
                'convenio_id' => $convenioId,
            ]);
            $paciente->created_at = $registro;
            $paciente->updated_at = $registro;
            $paciente->save();

            FichaClinica::create(['paciente_id' => $paciente->id]);
        }

        return Paciente::orderBy('created_at')->get(['id', 'created_at'])
            ->map(fn ($p) => ['id' => $p->id, 'desde' => $p->created_at->copy()->startOfDay()])
            ->all();
    }

    private function crearCitas(Carbon $hoy, array $boxes, array $pacientes): void
    {
        [$box1, $box2, $box3] = $boxes;
        $doctores = Doctor::where('activo', true)->orderBy('id')->get();
        $ahora = now();
        $citas = [];

        $fin = $hoy->copy()->addDays(14);
        for ($dia = $hoy->copy()->subMonths(6)->startOfMonth(); $dia->lte($fin); $dia->addDay()) {
            if ($dia->isWeekend()) {
                continue;
            }

            $disponibles = array_values(array_filter($pacientes, fn ($p) => $p['desde']->lte($dia)));
            if ($disponibles === []) {
                continue;
            }

            foreach ($doctores as $indice => $doctor) {
                $box = $indice === 0 ? $box1 : ($dia->dayOfWeekIso >= 4 ? $box3 : $box2);
                $hora = $dia->copy()->setTime(9, 0);
                $cierre = $dia->copy()->setTime(18, 0);

                while (true) {
                    $hora->addMinutes([0, 0, 15, 30][mt_rand(0, 3)]);
                    $duracion = [30, 45, 60][mt_rand(0, 2)];
                    $termino = $hora->copy()->addMinutes($duracion);

                    if ($termino->gt($cierre)) {
                        break;
                    }

                    if (mt_rand(1, 100) <= 72 && $this->libre($doctor->id, $box->id, $hora, $termino)) {
                        $citas[] = [
                            'paciente_id' => $disponibles[mt_rand(0, count($disponibles) - 1)]['id'],
                            'doctor_id' => $doctor->id,
                            'box_id' => $box->id,
                            'hora_inicio' => $hora->format('Y-m-d H:i:s'),
                            'hora_fin' => $termino->format('Y-m-d H:i:s'),
                            'estado' => $this->estadoCita($termino, $ahora),
                            'created_at' => $ahora,
                            'updated_at' => $ahora,
                        ];
                        $this->marcar($doctor->id, $box->id, $hora, $termino);
                    }

                    $hora = $termino;
                }
            }
        }

        foreach (array_chunk($citas, 500) as $lote) {
            DB::table('citas')->insert($lote);
        }
    }

    private function estadoCita(Carbon $termino, Carbon $ahora): string
    {
        $sorteo = mt_rand(1, 100);

        if ($termino->lte($ahora)) {
            return $sorteo <= 88 ? 'atendida' : 'cancelada';
        }

        if ($sorteo <= 8) {
            return 'cancelada';
        }

        return $sorteo <= 50 ? 'confirmada' : 'agendada';
    }

    private function crearPresupuestos(PresupuestoService $servicio, Carbon $hoy): void
    {
        $tratamientos = Tratamiento::where('activo', true)->pluck('id')->all();
        $atendidas = Cita::where('estado', 'atendida')->orderBy('hora_inicio')->get(['paciente_id', 'doctor_id', 'hora_inicio']);

        foreach ($atendidas as $cita) {
            if (mt_rand(1, 100) > 28) {
                continue;
            }

            $paciente = Paciente::with('convenio')->find($cita->paciente_id);
            $elegidos = (array) array_rand(array_flip($tratamientos), mt_rand(1, 3));
            $cantidades = [];
            foreach ($elegidos as $id) {
                $cantidades[$id] = mt_rand(1, 100) <= 75 ? 1 : 2;
            }

            $calculo = $servicio->calcular($paciente, $cantidades);
            $creado = $cita->hora_inicio->copy();
            $estado = $this->estadoPresupuesto($creado, $hoy);

            $presupuesto = new Presupuesto([
                'paciente_id' => $paciente->id,
                'doctor_id' => $cita->doctor_id,
                'estado' => $estado,
                'total_calculado' => $calculo['total'],
            ]);
            $presupuesto->created_at = $creado;
            $presupuesto->updated_at = $creado;
            $presupuesto->save();
            $presupuesto->detalles()->createMany($calculo['lineas']);

            $this->crearPagos($presupuesto, $estado, (float) $calculo['total'], $creado, $hoy);
        }
    }

    private function estadoPresupuesto(Carbon $creado, Carbon $hoy): string
    {
        $sorteo = mt_rand(1, 100);

        $antiguedad = $creado->diffInDays($hoy);

        if ($antiguedad < 10) {
            return $sorteo <= 45 ? 'borrador' : 'aceptado';
        }

        if ($antiguedad < 45) {
            return match (true) {
                $sorteo <= 40 => 'pagado',
                $sorteo <= 80 => 'aceptado',
                $sorteo <= 90 => 'anulado',
                default => 'borrador',
            };
        }

        return match (true) {
            $sorteo <= 78 => 'pagado',
            $sorteo <= 86 => 'aceptado',
            $sorteo <= 96 => 'anulado',
            default => 'borrador',
        };
    }

    private function crearPagos(Presupuesto $presupuesto, string $estado, float $total, Carbon $creado, Carbon $hoy): void
    {
        if (! in_array($estado, ['aceptado', 'pagado'], true) || $total <= 0) {
            return;
        }

        $objetivo = $estado === 'pagado' ? $total : floor($total * mt_rand(0, 70) / 100);
        if ($objetivo <= 0) {
            return;
        }

        $cuotas = mt_rand(1, 3);
        $restante = $objetivo;
        $pagos = [];

        for ($n = 1; $n <= $cuotas && $restante > 0; $n++) {
            $monto = $n === $cuotas ? $restante : max(1, floor($objetivo / $cuotas));
            $monto = min($monto, $restante);
            $restante -= $monto;

            $fecha = $creado->copy()->addDays(($n - 1) * mt_rand(7, 25));
            if ($fecha->gt($hoy)) {
                $fecha = $hoy->copy();
            }

            $pagos[] = [
                'presupuesto_id' => $presupuesto->id,
                'monto' => $monto,
                'fecha' => $fecha->toDateString(),
                'estado' => 'vigente',
                'created_at' => $fecha,
                'updated_at' => $fecha,
            ];
        }

        DB::table('pagos')->insert($pagos);
    }

    private function libre(int $doctorId, int $boxId, Carbon $inicio, Carbon $fin): bool
    {
        foreach (["d{$doctorId}", "b{$boxId}"] as $recurso) {
            foreach ($this->ocupado[$recurso][$inicio->toDateString()] ?? [] as [$desde, $hasta]) {
                if ($inicio->lt($hasta) && $fin->gt($desde)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function marcar(int $doctorId, int $boxId, Carbon $inicio, Carbon $fin): void
    {
        foreach (["d{$doctorId}", "b{$boxId}"] as $recurso) {
            $this->ocupado[$recurso][$inicio->toDateString()][] = [$inicio->copy(), $fin->copy()];
        }
    }
}
