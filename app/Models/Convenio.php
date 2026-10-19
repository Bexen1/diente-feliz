<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Convenio extends Model
{
    protected $table = 'convenios';

    protected $fillable = ['institucion', 'porcentaje_descuento'];

    protected function casts(): array
    {
        return ['porcentaje_descuento' => 'decimal:2'];
    }

    public function pacientes(): HasMany
    {
        return $this->hasMany(Paciente::class, 'convenio_id');
    }
}
