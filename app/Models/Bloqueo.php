<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bloqueo extends Model
{
    protected $table = 'bloqueos';

    protected $fillable = ['doctor_id', 'box_id', 'fecha_inicio', 'fecha_fin', 'motivo'];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class, 'box_id');
    }

    public function scopeEnFecha(Builder $consulta, Carbon $fecha): Builder
    {
        return $consulta->whereDate('fecha_inicio', '<=', $fecha)->whereDate('fecha_fin', '>=', $fecha);
    }

    public function scopeQueAfectan(Builder $consulta, ?int $doctorId, ?int $boxId): Builder
    {
        return $consulta->where(function ($q) use ($doctorId, $boxId) {
            $q->where(fn ($q) => $q->whereNull('doctor_id')->whereNull('box_id'));
            if ($doctorId) {
                $q->orWhere('doctor_id', $doctorId);
            }
            if ($boxId) {
                $q->orWhere('box_id', $boxId);
            }
        });
    }

    public function getAlcanceAttribute(): string
    {
        $partes = array_filter([$this->doctor?->nombre, $this->box?->nombre]);

        return $partes === [] ? 'Toda la clínica' : implode(' · ', $partes);
    }

    public function getPeriodoAttribute(): string
    {
        if ($this->fecha_inicio->isSameDay($this->fecha_fin)) {
            return $this->fecha_inicio->format('d/m/Y');
        }

        return 'del '.$this->fecha_inicio->format('d/m/Y').' al '.$this->fecha_fin->format('d/m/Y');
    }
}
