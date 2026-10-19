<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Presupuesto extends Model
{
    public const ESTADOS = ['borrador', 'aceptado', 'pagado', 'anulado'];

    protected $table = 'presupuestos';

    protected $fillable = ['paciente_id', 'doctor_id', 'estado', 'total_calculado'];

    protected function casts(): array
    {
        return ['total_calculado' => 'decimal:2'];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePresupuesto::class, 'presupuesto_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'presupuesto_id');
    }

    public function subtotal(): float
    {
        return (float) $this->detalles->sum('subtotal');
    }

    public function descuento(): float
    {
        return $this->subtotal() - (float) $this->total_calculado;
    }

    public function saldoPendiente(): float
    {
        $pagado = (float) $this->pagos()->where('estado', 'vigente')->sum('monto');

        return round((float) $this->total_calculado - $pagado, 2);
    }
}
