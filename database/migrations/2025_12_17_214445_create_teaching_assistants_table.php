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
        Schema::create('teaching_assistants', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique(); // Assumed unique for grouping
            $table->string('correo')->nullable();
            $table->string('ei', 50)->nullable();
            $table->string('ef', 50)->nullable();
            $table->string('profesion', 100)->nullable();
            $table->string('area', 100)->nullable(); // stored as ID or string? Checking existing seeder logic... seeder has integers for area/profesion sometimes, but strings in migration? Let's stick to nullable string/int to be safe or string as per original migration.
            // Original migration had: 
            // $table->string('profesion', 100)->nullable();
            // $table->string('area', 100)->nullable();
            // $table->string('adscripcion', 500)->nullable();
            
            $table->string('adscripcion', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teaching_assistants');
    }
};
