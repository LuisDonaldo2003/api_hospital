<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_pays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->onDelete('cascade');
            $table->double('amount');
            $table->string('method_payment', 250);
            $table->timestamps();
            $table->softDeletes();
            $table->char('trial442', 1)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_pays');
    }
};
