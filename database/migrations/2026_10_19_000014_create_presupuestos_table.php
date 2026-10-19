<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presupuestos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('doctores')->restrictOnDelete();
            $table->string('estado', 20)->default('borrador');
            $table->decimal('total_calculado', 10, 2)->default(0);
            $table->timestamps();
        });

        DB::statement("ALTER TABLE presupuestos ADD CONSTRAINT presupuestos_estado_valido
            CHECK (estado IN ('borrador', 'aceptado', 'pagado', 'anulado'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuestos');
    }
};
