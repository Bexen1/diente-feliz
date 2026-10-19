<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_presupuestos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presupuesto_id')->constrained('presupuestos')->restrictOnDelete();
            $table->foreignId('tratamiento_id')->constrained('tratamientos')->restrictOnDelete();
            $table->integer('cantidad');
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE detalle_presupuestos ADD CONSTRAINT detalle_cantidad_positiva
            CHECK (cantidad > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_presupuestos');
    }
};
