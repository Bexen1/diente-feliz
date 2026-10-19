<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->restrictOnDelete();
            $table->string('accion', 50);
            $table->string('tabla_afectada', 50);
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->jsonb('payload_cambios_json')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['tabla_afectada', 'registro_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_logs');
    }
};
