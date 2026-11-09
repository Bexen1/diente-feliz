<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    protected $table = 'pagos';

    public const METODOS = [
        'efectivo' => 'Efectivo',
        'debito' => 'Tarjeta de débito',
        'credito' => 'Tarjeta de crédito',
        'transferencia' => 'Transferencia',
    ];

    protected $fillable = ['presupuesto_id', 'monto', 'fecha', 'estado', 'metodo'];

    public function getNombreMetodoAttribute(): string
    {
        return self::METODOS[$this->metodo] ?? ucfirst((string) $this->metodo);
    }

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha' => 'date',
        ];
    }

    public function presupuesto(): BelongsTo
    {
        return $this->belongsTo(Presupuesto::class, 'presupuesto_id');
    }
}
