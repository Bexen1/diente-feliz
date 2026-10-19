<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctores')->restrictOnDelete();
            $table->unsignedSmallInteger('dia_semana');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE horarios ADD CONSTRAINT horarios_dia_valido
            CHECK (dia_semana BETWEEN 1 AND 7)');
        DB::statement('ALTER TABLE horarios ADD CONSTRAINT horarios_rango_valido
            CHECK (hora_fin > hora_inicio)');
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};
