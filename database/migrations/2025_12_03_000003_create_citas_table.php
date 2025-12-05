<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->nullable()->constrained('patients')->onDelete('cascade');
            $table->string('paciente_nombre', 200)->nullable(); // Para citas sin paciente registrado
            $table->string('paciente_telefono', 20)->nullable();
            $table->string('paciente_email', 100)->nullable();
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
            $table->date('fecha');
            $table->time('hora');
            $table->text('motivo');
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['pendiente', 'confirmada', 'en_progreso', 'completada', 'cancelada', 'no_asistio'])
                ->default('pendiente');
            $table->timestamp('fecha_cancelacion')->nullable();
            $table->text('motivo_cancelacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['doctor_id', 'fecha', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
