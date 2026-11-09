<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetallePresupuesto extends Model
{
    protected $table = 'detalle_presupuestos';

    protected $fillable = ['presupuesto_id', 'tratamiento_id', 'cantidad', 'cantidad_realizada', 'subtotal'];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'cantidad_realizada' => 'integer',
            'subtotal' => 'decimal:2',
        ];
    }

    public function pendientes(): int
    {
        return max(0, $this->cantidad - $this->cantidad_realizada);
    }

    public function presupuesto(): BelongsTo
    {
        return $this->belongsTo(Presupuesto::class, 'presupuesto_id');
    }

    public function tratamiento(): BelongsTo
    {
        return $this->belongsTo(Tratamiento::class, 'tratamiento_id');
    }
}
