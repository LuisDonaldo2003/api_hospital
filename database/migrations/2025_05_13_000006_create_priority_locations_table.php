<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('priority_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('municipality_id');
            $table->unsignedBigInteger('state_id');
            $table->string('location_name');
            $table->string('municipality_name');
            $table->string('state_name');
            $table->string('display_text');
            $table->string('normalized_name'); // Para búsqueda sin acentos
            $table->tinyInteger('priority_level')->default(1); // 1=mayor prioridad, 5=menor prioridad
            $table->timestamps();
            
            // Índices para búsqueda rápida
            $table->index(['priority_level', 'normalized_name']);
            $table->index(['state_id', 'priority_level']);
            $table->index('normalized_name');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
            $table->foreign('municipality_id')->references('id')->on('municipalities')->onDelete('cascade');
            $table->foreign('state_id')->references('id')->on('states')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('priority_locations');
    }
};
