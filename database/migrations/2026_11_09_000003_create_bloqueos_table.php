<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bloqueos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->nullable()->constrained('doctores')->restrictOnDelete();
            $table->foreignId('box_id')->nullable()->constrained('box')->restrictOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('motivo', 150);
            $table->timestamps();

            $table->index(['fecha_inicio', 'fecha_fin']);
        });

        DB::statement('ALTER TABLE bloqueos ADD CONSTRAINT bloqueos_rango_valido CHECK (fecha_fin >= fecha_inicio)');
    }

    public function down(): void
    {
        Schema::dropIfExists('bloqueos');
    }
};
