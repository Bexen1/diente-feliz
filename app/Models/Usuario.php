<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    protected $table = 'usuarios';

    protected $fillable = ['nombre', 'email', 'password', 'rol_id', 'activo'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    public function getRememberTokenName()
    {
        return '';
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class, 'usuario_id');
    }

    public function tieneRol(string ...$roles): bool
    {
        $nombre = $this->rol->nombre;

        return $nombre === Rol::ADMINISTRADOR || in_array($nombre, $roles, true);
    }

    public function esAdministrador(): bool
    {
        return $this->rol->nombre === Rol::ADMINISTRADOR;
    }

    public function esDentista(): bool
    {
        return $this->rol->nombre === Rol::DENTISTA;
    }
}
