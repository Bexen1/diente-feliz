<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->string('metodo', 20)->default('efectivo');
        });

        DB::statement("ALTER TABLE pagos ADD CONSTRAINT pagos_metodo_valido
            CHECK (metodo IN ('efectivo', 'debito', 'credito', 'transferencia'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE pagos DROP CONSTRAINT IF EXISTS pagos_metodo_valido');

        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn('metodo');
        });
    }
};
