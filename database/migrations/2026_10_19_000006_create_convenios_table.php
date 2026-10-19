<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('convenios', function (Blueprint $table) {
            $table->id();
            $table->string('institucion', 150);
            $table->decimal('porcentaje_descuento', 5, 2);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE convenios ADD CONSTRAINT convenios_descuento_rango
            CHECK (porcentaje_descuento BETWEEN 0 AND 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('convenios');
    }
};
