<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('archive', function (Blueprint $table) {
            $table->integer('archive_number')->primary(); // ID del expediente (ingreso manual)

            // Información del paciente
            $table->string('last_name_father', 100)->nullable();
            $table->string('last_name_mother', 100)->nullable();
            $table->string('name', 100)->nullable();
            $table->integer('age')->nullable();

            // Relación con género
            $table->unsignedBigInteger('gender_id')->nullable();
            $table->foreign('gender_id')->references('id')->on('genders')->nullOnDelete();

            // Contacto de emergencia
            $table->string('contact_last_name_father', 100)->nullable();
            $table->string('contact_last_name_mother', 100)->nullable();
            $table->string('contact_name', 100)->nullable();

            // Detalles adicionales
            $table->date('admission_date')->nullable();
            $table->string('address', 150)->nullable();

            // Localización como texto plano (sin relaciones con IDs)
            $table->string('location_text', 150)->nullable(); // Nombre de la localidad
            $table->string('municipality_text', 100)->nullable(); // Nombre del municipio
            $table->string('state_text', 100)->nullable(); // Nombre del estado

            // Timestamps y borrado suave
            $table->timestamps();

            // Índices para búsquedas eficientes
            $table->index(['location_text', 'municipality_text']);
            $table->index('municipality_text');
            $table->index('state_text');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive');
    }
};
