<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditoriaLog extends Model
{
    protected $table = 'auditoria_logs';

    protected $fillable = ['usuario_id', 'accion', 'tabla_afectada', 'registro_id', 'payload_cambios_json', 'ip'];

    protected function casts(): array
    {
        return ['payload_cambios_json' => 'array'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
