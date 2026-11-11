<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Permiso extends Model
{
    public const CATALOGO = [
        'pacientes.salud' => ['Pacientes', 'Ver y editar antecedentes de salud', [Rol::DENTISTA]],
        'fichas.gestionar' => ['Ficha clínica', 'Ficha clínica, odontograma, atenciones y radiografías', [Rol::DENTISTA]],
        'fichas.exportar' => ['Ficha clínica', 'Exportar la ficha del paciente (PDF, impresión y JSON)', [Rol::DENTISTA]],
        'plan.registrar' => ['Ficha clínica', 'Registrar tratamientos realizados del plan', [Rol::DENTISTA]],
        'agenda.todas' => ['Agenda', 'Ver y gestionar la agenda de todos los profesionales', [Rol::RECEPCIONISTA]],
        'presupuestos.ver' => ['Presupuestos y pagos', 'Ver presupuestos y descargar órdenes de tratamiento', [Rol::DENTISTA, Rol::RECEPCIONISTA]],
        'presupuestos.gestionar' => ['Presupuestos y pagos', 'Crear, editar, aceptar y anular presupuestos', [Rol::RECEPCIONISTA]],
        'pagos.registrar' => ['Presupuestos y pagos', 'Registrar pagos y emitir comprobantes', [Rol::RECEPCIONISTA]],
        'pagos.anular' => ['Presupuestos y pagos', 'Anular pagos', []],
        'mantenedores.gestionar' => ['Administración', 'Sucursales, box, convenios, tratamientos y bloqueos de agenda', []],
        'dashboard.ver' => ['Administración', 'Dashboard de indicadores', []],
        'reportes.ver' => ['Administración', 'Reportes y exportaciones', []],
        'auditoria.ver' => ['Administración', 'Registro de auditoría', []],
    ];

    protected $table = 'permisos';

    protected $fillable = ['clave', 'nombre', 'grupo', 'orden'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'permiso_rol', 'permiso_id', 'rol_id');
    }

    public static function sincronizarCatalogo(): void
    {
        $orden = 0;
        foreach (self::CATALOGO as $clave => [$grupo, $nombre]) {
            DB::table('permisos')->updateOrInsert(
                ['clave' => $clave],
                ['grupo' => $grupo, 'nombre' => $nombre, 'orden' => ++$orden, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public static function asignarPredeterminados(): void
    {
        $permisos = DB::table('permisos')->pluck('id', 'clave');
        $roles = DB::table('roles')->pluck('id', 'nombre');

        foreach (self::CATALOGO as $clave => [, , $predeterminados]) {
            foreach ($predeterminados as $rol) {
                if (isset($roles[$rol], $permisos[$clave])) {
                    DB::table('permiso_rol')->insertOrIgnore(['rol_id' => $roles[$rol], 'permiso_id' => $permisos[$clave]]);
                }
            }
        }
    }
}
