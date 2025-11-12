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
        Schema::table('teachings', function (Blueprint $table) {
            $table->string('ei', 50)->nullable()->change();
            $table->string('ef', 50)->nullable()->change();
            $table->string('profesion', 100)->nullable()->change();
            $table->string('area', 100)->nullable()->change();
            $table->string('adscripcion', 500)->nullable()->change();
            $table->text('tema')->nullable()->change();
            $table->string('horas', 50)->nullable()->change();
            $table->string('foja', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teachings', function (Blueprint $table) {
            $table->string('ei')->nullable()->change();
            $table->string('ef')->nullable()->change();
            $table->string('profesion')->nullable()->change();
            $table->string('area')->nullable()->change();
            $table->string('adscripcion')->nullable()->change();
            $table->string('tema')->nullable()->change();
            $table->string('horas')->nullable()->change();
            $table->string('foja')->nullable()->change();
        });
    }
};
