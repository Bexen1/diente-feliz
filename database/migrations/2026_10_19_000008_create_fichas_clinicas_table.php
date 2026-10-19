<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fichas_clinicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->unique()->constrained('pacientes')->restrictOnDelete();
            $table->text('evolucion_general')->nullable();
            $table->jsonb('estado_odontograma_json')->default(new Expression("'{}'::jsonb"));
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fichas_clinicas');
    }
};
