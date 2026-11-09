<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->reemplazarRestricciones(
            "('agendada', 'confirmada', 'reagendada', 'atendida', 'cancelada', 'no_asistio')",
            "estado NOT IN ('cancelada', 'no_asistio')"
        );
    }

    public function down(): void
    {
        DB::table('citas')->where('estado', 'no_asistio')->update(['estado' => 'cancelada']);

        $this->reemplazarRestricciones(
            "('agendada', 'confirmada', 'reagendada', 'atendida', 'cancelada')",
            "estado <> 'cancelada'"
        );
    }

    private function reemplazarRestricciones(string $estados, string $condicion): void
    {
        DB::statement('ALTER TABLE citas DROP CONSTRAINT IF EXISTS citas_estado_valido');
        DB::statement('ALTER TABLE citas DROP CONSTRAINT IF EXISTS citas_sin_cruce_doctor');
        DB::statement('ALTER TABLE citas DROP CONSTRAINT IF EXISTS citas_sin_cruce_box');

        DB::statement("ALTER TABLE citas ADD CONSTRAINT citas_estado_valido CHECK (estado IN {$estados})");
        DB::statement("ALTER TABLE citas ADD CONSTRAINT citas_sin_cruce_doctor
            EXCLUDE USING gist (doctor_id WITH =, tsrange(hora_inicio, hora_fin, '[)') WITH &&)
            WHERE ({$condicion})");
        DB::statement("ALTER TABLE citas ADD CONSTRAINT citas_sin_cruce_box
            EXCLUDE USING gist (box_id WITH =, tsrange(hora_inicio, hora_fin, '[)') WITH &&)
            WHERE ({$condicion})");
    }
};
