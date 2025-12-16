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
        Schema::table('doctors', function (Blueprint $table) {
            // Add the missing column for General Medical relations
            $table->foreignId('general_medical_id')
                  ->nullable()
                  ->after('especialidad_id')
                  ->constrained('general_medicals')
                  ->nullOnDelete();
            
            // Allow specialists to be null if the doctor is a General Medical doctor
            $table->unsignedBigInteger('especialidad_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropForeign(['general_medical_id']);
            $table->dropColumn('general_medical_id');
            
            // We generally don't revert nullable() changes as it might break data integrity
            // if we now have records with null specialty.
        });
    }
};
