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
        Schema::create('sent_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date')->unique();
            $table->timestamp('sent_at');
            $table->enum('report_type', ['scheduled', 'recovered', 'manual'])->default('scheduled');
            $table->integer('total_activities')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('report_date');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sent_reports');
    }
};
