<?php

namespace Database\Seeders;

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            Rol::ADMINISTRADOR => 'Gestiona usuarios, catálogos, finanzas y reportes. Tiene además todos los permisos de los demás roles.',
            Rol::DENTISTA => 'Atiende pacientes: ficha clínica, odontograma, radiografías y su propia agenda.',
            Rol::RECEPCIONISTA => 'Atiende la recepción: pacientes, agenda de todos los profesionales, presupuestos y pagos.',
        ];

        foreach ($roles as $nombre => $descripcion) {
            Rol::create(['nombre' => $nombre, 'descripcion' => $descripcion]);
        }

        Permiso::sincronizarCatalogo();
        Permiso::asignarPredeterminados();
    }
}
