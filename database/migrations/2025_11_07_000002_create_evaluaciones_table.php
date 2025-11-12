<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teaching_id')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_limite')->nullable();
            $table->string('especialidad')->nullable();
            $table->string('nombre');
            $table->string('estado')->default('PENDIENTE');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('teaching_id')->references('id')->on('teachings')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones');
    }
};
