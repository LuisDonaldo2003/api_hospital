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
        Schema::table('citas', function (Blueprint $table) {
            // Agregar nueva columna
            $table->unsignedBigInteger('appointment_service_id')->nullable()->after('doctor_id');
            
            // Foreign key
            $table->foreign('appointment_service_id')->references('id')->on('appointment_services')->onDelete('set null');
            
            // Hacer nullable las columnas antiguas
            if (Schema::hasColumn('citas', 'especialidad_id')) {
                $table->unsignedBigInteger('especialidad_id')->nullable()->change();
            }
            if (Schema::hasColumn('citas', 'general_medical_id')) {
                $table->unsignedBigInteger('general_medical_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropForeign(['appointment_service_id']);
            $table->dropColumn('appointment_service_id');
        });
    }
};
