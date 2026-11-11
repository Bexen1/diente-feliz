<?php

namespace App\Policies;

use App\Models\Cita;
use App\Models\Usuario;

class CitaPolicy
{
    public function modificar(Usuario $usuario, Cita $cita): bool
    {
        return $this->agendarPara($usuario, $cita->doctor_id);
    }

    public function agendarPara(Usuario $usuario, int $doctorId): bool
    {
        return $usuario->veTodaLaAgenda()
            || ($usuario->doctor !== null && $doctorId === $usuario->doctor->id);
    }
}
