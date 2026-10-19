<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evoluciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ficha_id')->constrained('fichas_clinicas')->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('doctores')->restrictOnDelete();
            $table->date('fecha');
            $table->text('diagnostico');
            $table->text('tratamiento_realizado')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evoluciones');
    }
};
