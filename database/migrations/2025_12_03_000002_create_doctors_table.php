<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_completo', 255);
            $table->foreignId('especialidad_id')->constrained('especialidades')->onDelete('cascade');
            $table->enum('turno', ['Matutino', 'Vespertino', 'Mixto'])->default('Matutino');
            
            // Horarios Matutino
            $table->time('hora_inicio_matutino')->nullable();
            $table->time('hora_fin_matutino')->nullable();
            
            // Horarios Vespertino
            $table->time('hora_inicio_vespertino')->nullable();
            $table->time('hora_fin_vespertino')->nullable();
            
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
