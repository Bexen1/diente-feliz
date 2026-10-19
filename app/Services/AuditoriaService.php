<?php

namespace App\Services;

use App\Models\AuditoriaLog;
use App\Models\Paciente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditoriaService
{
    private const CAMPOS_OCULTOS = ['password', 'created_at', 'updated_at'];

    public static function registrar(
        string $accion,
        string $tabla,
        ?int $registroId = null,
        ?array $antes = null,
        ?array $despues = null,
        ?int $usuarioId = null,
    ): void {
        $payload = ($antes === null && $despues === null)
            ? null
            : ['antes' => self::limpiar($antes), 'despues' => self::limpiar($despues)];

        AuditoriaLog::create([
            'usuario_id' => $usuarioId ?? Auth::id(),
            'accion' => $accion,
            'tabla_afectada' => $tabla,
            'registro_id' => $registroId,
            'payload_cambios_json' => $payload,
            'ip' => request()->ip(),
        ]);
    }

    public static function creado(Model $modelo): void
    {
        self::registrar('crear', $modelo->getTable(), $modelo->getKey(), null, $modelo->getAttributes());
    }

    public static function actualizado(Model $modelo, array $original, string $accion = 'actualizar'): void
    {
        $cambios = $modelo->getChanges();
        unset($cambios['updated_at']);

        if ($cambios === []) {
            return;
        }

        $antes = array_intersect_key($original, $cambios);
        self::registrar($accion, $modelo->getTable(), $modelo->getKey(), $antes, $cambios);
    }

    private static function limpiar(?array $datos): ?array
    {
        if ($datos === null) {
            return null;
        }

        foreach (self::CAMPOS_OCULTOS as $campo) {
            unset($datos[$campo]);
        }

        foreach (Paciente::CAMPOS_SALUD as $campo) {
            if (array_key_exists($campo, $datos)) {
                $datos[$campo] = '(dato de salud cifrado)';
            }
        }

        return $datos;
    }
}
