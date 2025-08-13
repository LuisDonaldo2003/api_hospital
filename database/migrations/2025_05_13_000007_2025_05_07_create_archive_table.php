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

            // Relación con localidades
            $table->unsignedBigInteger('location_id')->nullable();
            $table->foreign('location_id')->references('id')->on('locations')->nullOnDelete();

            // Timestamps y borrado suave
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive');
    }
};
