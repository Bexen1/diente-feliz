<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cita extends Model
{
    public const DURACIONES = [30, 45, 60];

    public const ESTADOS = ['agendada', 'confirmada', 'reagendada', 'atendida', 'cancelada'];

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

    public function getDuracionAttribute(): int
    {
        return (int) $this->hora_inicio->diffInMinutes($this->hora_fin);
    }
}
