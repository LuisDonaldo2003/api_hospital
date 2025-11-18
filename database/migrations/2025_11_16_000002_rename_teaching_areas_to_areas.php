<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Renombra la tabla teaching_areas a areas para consistencia con el frontend
     * y agrega el campo descripcion
     */
    public function up(): void
    {
        // Renombrar la tabla
        Schema::rename('teaching_areas', 'areas');
        
        // Agregar campo descripcion si no existe
        Schema::table('areas', function (Blueprint $table) {
            if (!Schema::hasColumn('areas', 'descripcion')) {
                $table->string('descripcion', 500)->nullable()->after('nombre');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar campo descripcion
        Schema::table('areas', function (Blueprint $table) {
            if (Schema::hasColumn('areas', 'descripcion')) {
                $table->dropColumn('descripcion');
            }
        });
        
        // Renombrar de vuelta
        Schema::rename('areas', 'teaching_areas');
    }
};
