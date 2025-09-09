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
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('action_type', 50); // create, read, update, delete, login, logout, export, import
            $table->string('module', 50); // archive, users, reports, config, backup
            $table->text('description'); // Descripción legible de la actividad
            $table->string('affected_table', 100)->nullable(); // Tabla afectada
            $table->unsignedBigInteger('affected_record_id')->nullable(); // ID del registro afectado
            $table->json('old_values')->nullable(); // Valores anteriores (para updates)
            $table->json('new_values')->nullable(); // Valores nuevos (para creates/updates)
            $table->ipAddress('ip_address')->nullable(); // IP del usuario
            $table->text('user_agent')->nullable(); // User agent del navegador
            $table->timestamps();

            // Índices para mejorar el rendimiento
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'created_at']);
            $table->index(['action_type', 'created_at']);
            $table->index(['module', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activities');
    }
};
