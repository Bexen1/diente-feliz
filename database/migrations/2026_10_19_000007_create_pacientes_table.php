<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->string('rut', 12)->unique();
            $table->string('nombre', 150);
            $table->date('fecha_nacimiento')->nullable();
            $table->string('telefono', 20);
            $table->string('email', 150)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->string('contacto_emergencia_nombre', 150)->nullable();
            $table->string('contacto_emergencia_telefono', 20)->nullable();
            $table->foreignId('convenio_id')->nullable()->constrained('convenios')->restrictOnDelete();
            $table->text('alergias')->nullable();
            $table->text('patologias')->nullable();
            $table->text('medicamentos')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
