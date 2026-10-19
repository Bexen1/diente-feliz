<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Horario;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Rol::pluck('id', 'nombre');
        $password = 'DienteFeliz2026';

        Usuario::create([
            'nombre' => 'Admin Clínica',
            'email' => 'admin@dientefeliz.test',
            'password' => $password,
            'rol_id' => $roles[Rol::ADMINISTRADOR],
        ]);

        Usuario::create([
            'nombre' => 'Valentina Mora',
            'email' => 'valentina@dientefeliz.test',
            'password' => $password,
            'rol_id' => $roles[Rol::RECEPCIONISTA],
        ]);

        $dentistas = [
            ['Dra. Camila Rojas', 'camila@dientefeliz.test', 'Odontología general'],
            ['Dr. Matías Soto', 'matias@dientefeliz.test', 'Endodoncia'],
        ];

        foreach ($dentistas as [$nombre, $email, $especialidad]) {
            $usuario = Usuario::create([
                'nombre' => $nombre,
                'email' => $email,
                'password' => $password,
                'rol_id' => $roles[Rol::DENTISTA],
            ]);

            $doctor = Doctor::create(['usuario_id' => $usuario->id, 'especialidad' => $especialidad]);

            for ($dia = 1; $dia <= 5; $dia++) {
                Horario::create([
                    'doctor_id' => $doctor->id,
                    'dia_semana' => $dia,
                    'hora_inicio' => '09:00',
                    'hora_fin' => '18:00',
                ]);
            }
        }
    }
}
