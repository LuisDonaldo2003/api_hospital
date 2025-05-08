<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('datos', function (Blueprint $table) {
            $table->integer('numero')->primary();
            $table->string('app', 45)->nullable();
            $table->string('appm', 45)->nullable();
            $table->string('nom', 45)->nullable();
            $table->string('edad', 45)->nullable();
            $table->string('sexo', 45)->nullable();
            $table->string('clasificacion', 45)->nullable();
            $table->string('app1', 45)->nullable();
            $table->string('appm1', 45)->nullable();
            $table->string('nom1', 45)->nullable();
            $table->date('fecha')->nullable();
            $table->string('direccion', 45)->nullable();
            $table->string('localidad', 45)->nullable();
            $table->string('municipio', 45)->nullable();
            $table->char('trial304', 1)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('datos');
    }
};

