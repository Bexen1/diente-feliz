<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evoluciones', function (Blueprint $table) {
            $table->foreignId('detalle_presupuesto_id')->nullable()->constrained('detalle_presupuestos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('evoluciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('detalle_presupuesto_id');
        });
    }
};
