<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Rol extends Model
{
    public const ADMINISTRADOR = 'Administrador';

    public const DENTISTA = 'Dentista';

    public const RECEPCIONISTA = 'Recepcionista';

    protected $table = 'roles';

    protected $fillable = ['nombre', 'descripcion'];

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'rol_id');
    }

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'permiso_rol', 'rol_id', 'permiso_id');
    }

    public function clavesPermisos(): Collection
    {
        return $this->permisos->pluck('clave');
    }

    public function esAdministrador(): bool
    {
        return $this->nombre === self::ADMINISTRADOR;
    }
}
