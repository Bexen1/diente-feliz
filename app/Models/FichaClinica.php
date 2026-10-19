<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FichaClinica extends Model
{
    protected $table = 'fichas_clinicas';

    protected $fillable = ['paciente_id', 'evolucion_general', 'estado_odontograma_json'];

    protected function casts(): array
    {
        return ['estado_odontograma_json' => 'array'];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function radiografias(): HasMany
    {
        return $this->hasMany(Radiografia::class, 'ficha_id');
    }

    public function evoluciones(): HasMany
    {
        return $this->hasMany(Evolucion::class, 'ficha_id');
    }
}
