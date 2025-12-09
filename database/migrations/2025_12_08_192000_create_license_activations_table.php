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
        Schema::create('license_activations', function (Blueprint $table) {
            $table->id();
            
            // Hash de la licencia
            $table->string('license_key', 500);
            
            // Firma de hardware del servidor donde se activó
            $table->string('hardware_signature', 64);
            
            // Información completa del hardware
            $table->json('hardware_info')->nullable();
            
            // Timestamps de activación
            $table->timestamp('activated_at');
            $table->timestamp('deactivated_at')->nullable();
            
            // Estado de la activación
            $table->boolean('is_active')->default(true);
            
            // IP desde donde se activó
            $table->string('activation_ip', 45)->nullable();
            
            // Usuario que activó (puede ser null para activación inicial)
            $table->unsignedBigInteger('activated_by')->nullable();
            
            // Información adicional del servidor
            $table->json('server_info')->nullable();
            
            $table->timestamps();
            
            // Índices para búsquedas rápidas
            $table->index('license_key');
            $table->index('hardware_signature');
            $table->index('is_active');
            $table->index(['license_key', 'is_active']);
            
            // Relación con usuarios
            $table->foreign('activated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_activations');
    }
};
