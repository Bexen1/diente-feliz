<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Radiografia extends Model
{
    protected $table = 'radiografias';

    protected $fillable = ['ficha_id', 'ruta_archivo', 'fecha', 'diagnostico'];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    public function ficha(): BelongsTo
    {
        return $this->belongsTo(FichaClinica::class, 'ficha_id');
    }
}
