<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_schedule_join_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_schedule_day_id')->constrained('doctor_schedule_days')->onDelete('cascade');
            $table->foreignId('doctor_schedule_hour_id')->constrained('doctor_schedule_hours')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
            $table->char('trial442', 1)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_schedule_join_hours');
    }
};
