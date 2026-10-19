<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([Rol::ADMINISTRADOR, Rol::DENTISTA, Rol::RECEPCIONISTA] as $nombre) {
            Rol::create(['nombre' => $nombre]);
        }
    }
}
