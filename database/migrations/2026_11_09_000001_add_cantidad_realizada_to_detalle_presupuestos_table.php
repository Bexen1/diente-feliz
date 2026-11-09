<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalle_presupuestos', function (Blueprint $table) {
            $table->integer('cantidad_realizada')->default(0);
        });

        DB::statement('ALTER TABLE detalle_presupuestos ADD CONSTRAINT detalle_realizado_valido
            CHECK (cantidad_realizada BETWEEN 0 AND cantidad)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE detalle_presupuestos DROP CONSTRAINT IF EXISTS detalle_realizado_valido');

        Schema::table('detalle_presupuestos', function (Blueprint $table) {
            $table->dropColumn('cantidad_realizada');
        });
    }
};
