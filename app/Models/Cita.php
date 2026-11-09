<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cita extends Model
{
    public const DURACIONES = [30, 45, 60];

    public const ESTADOS = ['agendada', 'confirmada', 'reagendada', 'atendida', 'cancelada', 'no_asistio'];

    public const ESTADOS_LIBERAN_HORARIO = ['cancelada', 'no_asistio'];

    public const ESTADOS_VIGENTES = ['agendada', 'confirmada', 'reagendada'];

    public const ETIQUETAS = [
        'agendada' => 'Agendada',
        'confirmada' => 'Confirmada',
        'reagendada' => 'Reagendada',
        'atendida' => 'Atendida',
        'cancelada' => 'Cancelada',
        'no_asistio' => 'No asistió',
    ];

    protected $table = 'citas';

    protected $fillable = ['paciente_id', 'doctor_id', 'box_id', 'hora_inicio', 'hora_fin', 'estado'];

    protected function casts(): array
    {
        return [
            'hora_inicio' => 'datetime',
            'hora_fin' => 'datetime',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class, 'box_id');
    }

    public function getEtiquetaEstadoAttribute(): string
    {
        return self::ETIQUETAS[$this->estado] ?? ucfirst($this->estado);
    }

    public function getDuracionAttribute(): int
    {
        return (int) $this->hora_inicio->diffInMinutes($this->hora_fin);
    }
}
