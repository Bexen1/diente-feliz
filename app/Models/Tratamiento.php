<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tratamiento extends Model
{
    protected $table = 'tratamientos';

    protected $fillable = ['nombre', 'costo_base', 'activo'];

    protected function casts(): array
    {
        return [
            'costo_base' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePresupuesto::class, 'tratamiento_id');
    }
}
