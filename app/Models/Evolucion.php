<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evolucion extends Model
{
    protected $table = 'evoluciones';

    protected $fillable = ['ficha_id', 'doctor_id', 'detalle_presupuesto_id', 'fecha', 'diagnostico', 'tratamiento_realizado'];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    public function ficha(): BelongsTo
    {
        return $this->belongsTo(FichaClinica::class, 'ficha_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function detalle(): BelongsTo
    {
        return $this->belongsTo(DetallePresupuesto::class, 'detalle_presupuesto_id');
    }
}
