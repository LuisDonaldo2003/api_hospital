<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachings', function (Blueprint $table) {
            $table->id();
            $table->string('correo')->nullable();
            $table->string('ei', 50)->nullable();
            $table->string('ef', 50)->nullable();
            $table->string('profesion', 100)->nullable();
            $table->string('nombre');
            $table->string('area', 100)->nullable();
            $table->string('adscripcion', 500)->nullable();
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

    public function down(): void
    {
        Schema::dropIfExists('teachings');
    }
};
