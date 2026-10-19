<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Paciente extends Model
{
    protected $table = 'pacientes';

    protected $fillable = [
        'rut', 'nombre', 'fecha_nacimiento', 'telefono', 'email', 'direccion',
        'contacto_emergencia_nombre', 'contacto_emergencia_telefono', 'convenio_id',
        'alergias', 'patologias', 'medicamentos', 'activo',
    ];

    public const CAMPOS_SALUD = ['alergias', 'patologias', 'medicamentos'];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'activo' => 'boolean',
            'alergias' => 'encrypted',
            'patologias' => 'encrypted',
            'medicamentos' => 'encrypted',
        ];
    }

    public function convenio(): BelongsTo
    {
        return $this->belongsTo(Convenio::class, 'convenio_id');
    }

    public function ficha(): HasOne
    {
        return $this->hasOne(FichaClinica::class, 'paciente_id');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'paciente_id');
    }

    public function presupuestos(): HasMany
    {
        return $this->hasMany(Presupuesto::class, 'paciente_id');
    }
}
