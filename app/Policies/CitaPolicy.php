<?php

namespace App\Policies;

use App\Models\Cita;
use App\Models\Usuario;

class CitaPolicy
{
    public function modificar(Usuario $usuario, Cita $cita): bool
    {
        return $usuario->tieneRol('Recepcionista')
            || ($usuario->esDentista() && $cita->doctor_id === $usuario->doctor?->id);
    }

    public function agendarPara(Usuario $usuario, int $doctorId): bool
    {
        return $usuario->tieneRol('Recepcionista')
            || ($usuario->esDentista() && $doctorId === $usuario->doctor?->id);
    }
}
