<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_schedule_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('day', 50);
            $table->timestamps();
            $table->softDeletes();
            $table->char('trial442', 1)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_schedule_days');
    }
};
