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
        Schema::create('teaching_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_assistant_id')->constrained('teaching_assistants')->onDelete('cascade');
            $table->string('nombre_evento')->nullable();
            $table->text('tema')->nullable();
            $table->date('fecha')->nullable();
            $table->string('horas', 50)->nullable();
            $table->string('foja', 100)->nullable();
            $table->unsignedBigInteger('modalidad_id')->nullable();
            $table->unsignedBigInteger('participacion_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teaching_events');
    }
};
