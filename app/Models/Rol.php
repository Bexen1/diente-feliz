<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    public const ADMINISTRADOR = 'Administrador';

    public const DENTISTA = 'Dentista';

    public const RECEPCIONISTA = 'Recepcionista';

    public const MATRIZ_PERMISOS = [
        'Usuarios, roles y doctores' => ['Total', '—', '—'],
        'Sucursales, box y convenios' => ['Total', 'Lectura', 'Lectura'],
        'Tratamientos y bloqueos de agenda' => ['Total', '—', '—'],
        'Pacientes (datos de contacto)' => ['Total', 'Crear y editar', 'Crear y editar'],
        'Antecedentes de salud' => ['Total', 'Crear y editar', '—'],
        'Ficha clínica, odontograma y radiografías' => ['Total', 'Total', '—'],
        'Exportar ficha del paciente' => ['Sí', 'Sí', '—'],
        'Agenda (ver)' => ['Todos los profesionales', 'Solo la propia', 'Todos los profesionales'],
        'Citas (agendar, reagendar, cancelar, inasistencia)' => ['Sí', 'Solo en su agenda', 'Sí'],
        'Presupuestos' => ['Total', 'Lectura', 'Crear y editar'],
        'Plan de tratamiento (registrar realizado)' => ['Sí', 'Sí', 'Lectura'],
        'Pagos y comprobantes' => ['Total, incluye anular', '—', 'Registrar y consultar'],
        'Dashboard y reportes' => ['Sí', '—', '—'],
        'Registro de auditoría' => ['Solo lectura', '—', '—'],
    ];

    protected $table = 'roles';

    protected $fillable = ['nombre', 'descripcion'];

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'rol_id');
    }

    public static function columnaMatriz(string $nombre): int
    {
        return array_search($nombre, [self::ADMINISTRADOR, self::DENTISTA, self::RECEPCIONISTA], true);
    }
}
