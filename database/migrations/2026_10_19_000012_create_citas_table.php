<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('doctores')->restrictOnDelete();
            $table->foreignId('box_id')->constrained('box')->restrictOnDelete();
            $table->timestamp('hora_inicio');
            $table->timestamp('hora_fin');
            $table->string('estado', 20)->default('agendada');
            $table->timestamps();

            $table->index(['doctor_id', 'hora_inicio']);
            $table->index(['box_id', 'hora_inicio']);
        });

        DB::statement("ALTER TABLE citas ADD CONSTRAINT citas_duracion_valida
            CHECK (hora_fin - hora_inicio IN (interval '30 minutes', interval '45 minutes', interval '60 minutes'))");
        DB::statement("ALTER TABLE citas ADD CONSTRAINT citas_estado_valido
            CHECK (estado IN ('agendada', 'confirmada', 'reagendada', 'atendida', 'cancelada'))");

        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
        DB::statement("ALTER TABLE citas ADD CONSTRAINT citas_sin_cruce_doctor
            EXCLUDE USING gist (doctor_id WITH =, tsrange(hora_inicio, hora_fin, '[)') WITH &&)
            WHERE (estado <> 'cancelada')");
        DB::statement("ALTER TABLE citas ADD CONSTRAINT citas_sin_cruce_box
            EXCLUDE USING gist (box_id WITH =, tsrange(hora_inicio, hora_fin, '[)') WITH &&)
            WHERE (estado <> 'cancelada')");
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
