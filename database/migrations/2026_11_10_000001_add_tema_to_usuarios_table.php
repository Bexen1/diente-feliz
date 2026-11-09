<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('tema', 10)->default('auto');
        });

        DB::statement("ALTER TABLE usuarios ADD CONSTRAINT usuarios_tema_valido CHECK (tema IN ('auto', 'claro', 'oscuro'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE usuarios DROP CONSTRAINT IF EXISTS usuarios_tema_valido');

        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('tema');
        });
    }
};
