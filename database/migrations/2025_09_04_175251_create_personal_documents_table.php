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
        Schema::create('personal_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_id')->constrained('personals')->onDelete('cascade');
            $table->enum('tipo_documento', [
                'Acta de nacimiento',
                'Comprobante de domicilio',
                'CURP',
                'INE',
                'Título profesional',
                'Constancias de cursos',
                'Cédula profesional'
            ]);
            $table->string('nombre_archivo');
            $table->string('ruta_archivo');
            $table->string('tipo_mime');
            $table->integer('tamaño_archivo');
            $table->timestamp('fecha_subida');
            $table->timestamps();
            
            // Índice eliminado para permitir multiples constancias
            // $table->unique(['personal_id', 'tipo_documento']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_documents');
    }
};
