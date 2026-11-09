<?php

namespace App\Console\Commands;

use App\Services\CitaService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class IntentoReservaCommand extends Command
{
    protected $signature = 'agenda:intento-reserva
        {paciente : Id del paciente}
        {doctor : Id del doctor}
        {box : Id del box}
        {fecha : Fecha Y-m-d}
        {hora : Hora H:i}
        {--duracion=30 : Duración en minutos}
        {--inicio= : Instante Unix en que se dispara el intento}';

    protected $description = 'Intenta agendar una cita con las mismas reglas de la aplicación';

    public function handle(CitaService $citas): int
    {
        $inicio = (float) $this->option('inicio');
        if ($inicio > 0 && microtime(true) < $inicio) {
            usleep((int) (($inicio - microtime(true)) * 1000000));
        }

        try {
            $cita = $citas->agendar([
                'paciente_id' => (int) $this->argument('paciente'),
                'doctor_id' => (int) $this->argument('doctor'),
                'box_id' => (int) $this->argument('box'),
                'fecha' => $this->argument('fecha'),
                'hora' => $this->argument('hora'),
                'duracion' => (int) $this->option('duracion'),
            ]);
            $this->line('aceptada '.$cita->id);
        } catch (ValidationException $e) {
            $this->line('rechazada '.collect($e->errors())->flatten()->first());
        }

        return self::SUCCESS;
    }
}
