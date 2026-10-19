<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiografias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ficha_id')->constrained('fichas_clinicas')->restrictOnDelete();
            $table->string('ruta_archivo', 255);
            $table->date('fecha');
            $table->text('diagnostico')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiografias');
    }
};
