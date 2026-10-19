<?php

namespace Database\Seeders;

use App\Models\Box;
use App\Models\Convenio;
use App\Models\Sucursal;
use App\Models\Tratamiento;
use Illuminate\Database\Seeder;

class ClinicaSeeder extends Seeder
{
    public function run(): void
    {
        $matriz = Sucursal::create(['nombre' => 'Casa Matriz', 'direccion' => 'Av. Principal 123, Concepción']);

        Box::create(['nombre' => 'Box 1', 'sucursal_id' => $matriz->id]);
        Box::create(['nombre' => 'Box 2', 'sucursal_id' => $matriz->id]);

        Convenio::create(['institucion' => 'Caja de Compensación Andes', 'porcentaje_descuento' => 15]);
        Convenio::create(['institucion' => 'Colegio de Profesores', 'porcentaje_descuento' => 10]);

        $tratamientos = [
            ['Limpieza dental', 25000],
            ['Restauración con resina', 45000],
            ['Extracción simple', 30000],
            ['Endodoncia unirradicular', 120000],
            ['Radiografía panorámica', 20000],
        ];

        foreach ($tratamientos as [$nombre, $costo]) {
            Tratamiento::create(['nombre' => $nombre, 'costo_base' => $costo]);
        }
    }
}
