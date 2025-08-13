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
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('surname')->nullable();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('mobile')->nullable();
            $table->date('birth_date')->nullable();
            $table->unsignedBigInteger('gender_id')->nullable();

            // Nuevos campos
            // $table->string('profile')->nullable(); // perfil textual
            $table->string('curp', 18)->nullable();
            $table->string('ine', 18)->nullable();
            $table->string('rfc', 13)->nullable();
            $table->string('attendance_number', 20)->nullable();
            $table->string('professional_license', 20)->nullable();
            $table->string('funcion_real')->nullable(); // antes llamado 'function'
            $table->json('settings')->nullable();

            // Claves foráneas
            $table->foreignId('departament_id')->nullable()->constrained('departaments')->nullOnDelete();
            $table->foreignId('profile_id')->nullable()->constrained('profiles')->nullOnDelete();
            $table->foreignId('contract_type_id')->nullable()->constrained('contract_types')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->index(); // usado por algunas relaciones en Laravel
            $table->foreign('gender_id')->references('id')->on('genders')->onDelete('set null');


            $table->string('avatar')->nullable();

            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
