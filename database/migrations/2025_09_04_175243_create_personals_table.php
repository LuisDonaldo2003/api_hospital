<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('personals', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('apellidos');
            $table->enum('tipo', ['Clínico', 'No Clínico']);
            $table->string('rfc', 13)->nullable()->comment('Registro Federal de Contribuyentes');
            $table->string('numero_checador', 10)->nullable()->comment('Número de checador para control de asistencia');
            $table->date('fecha_ingreso')->default(now());
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            // Índices para búsqueda optimizada
            $table->index(['rfc']);
            $table->index(['numero_checador']);
            $table->index(['nombre', 'apellidos']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personals');
    }
};
