<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presupuesto_id')->constrained('presupuestos')->restrictOnDelete();
            $table->decimal('monto', 10, 2);
            $table->date('fecha');
            $table->string('estado', 20)->default('vigente');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE pagos ADD CONSTRAINT pagos_monto_positivo CHECK (monto > 0)');
        DB::statement("ALTER TABLE pagos ADD CONSTRAINT pagos_estado_valido
            CHECK (estado IN ('vigente', 'anulado'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
