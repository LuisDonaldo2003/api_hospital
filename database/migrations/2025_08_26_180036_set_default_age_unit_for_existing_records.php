<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primero, establecer 'años' como valor por defecto para todos
        DB::table('archive')
            ->whereNull('age_unit')
            ->orWhere('age_unit', '')
            ->orWhere('age_unit', 'years')
            ->update(['age_unit' => 'años']);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    return;
    }
};
