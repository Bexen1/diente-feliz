<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    protected $table = 'doctores';

    protected $fillable = ['usuario_id', 'especialidad', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'doctor_id');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class, 'doctor_id');
    }

    public function presupuestos(): HasMany
    {
        return $this->hasMany(Presupuesto::class, 'doctor_id');
    }

    public function getNombreAttribute(): string
    {
        return $this->usuario->nombre;
    }
}
