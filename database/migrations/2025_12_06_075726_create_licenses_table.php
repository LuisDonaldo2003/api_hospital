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
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('institution')->nullable();
            $table->string('license_key', 500); // Hash del archivo de licencia
            $table->text('license_data'); // Datos encriptados de la licencia
            $table->enum('type', ['monthly', 'annual', 'permanent'])->default('permanent');
            $table->timestamp('activated_at');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('features')->nullable(); // JSON con características habilitadas
            $table->string('allowed_domain')->nullable();
            $table->string('signature', 500);
            $table->unsignedBigInteger('activated_by')->nullable();
            $table->string('activation_ip', 45)->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->foreign('activated_by')->references('id')->on('users')->onDelete('set null');
            $table->index('is_active');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
