<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tratamientos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->decimal('costo_base', 10, 2);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE tratamientos ADD CONSTRAINT tratamientos_costo_positivo
            CHECK (costo_base >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('tratamientos');
    }
};
