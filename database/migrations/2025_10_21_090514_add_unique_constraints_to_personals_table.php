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
        Schema::table('personals', function (Blueprint $table) {
            // Agregar constraint único para RFC
            $table->unique('rfc', 'personals_rfc_unique');
            
            // Agregar constraint único para número de checador
            $table->unique('numero_checador', 'personals_numero_checador_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            // Eliminar constraints únicos
            $table->dropUnique('personals_rfc_unique');
            $table->dropUnique('personals_numero_checador_unique');
        });
    }
};
