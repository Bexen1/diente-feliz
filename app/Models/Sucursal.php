<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sucursal extends Model
{
    protected $table = 'sucursales';

    protected $fillable = ['nombre', 'direccion'];

    public function boxes(): HasMany
    {
        return $this->hasMany(Box::class, 'sucursal_id');
    }
}
