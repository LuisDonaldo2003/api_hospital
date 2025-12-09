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
        Schema::table('licenses', function (Blueprint $table) {
            // Firma de hardware autorizada para esta licencia
            $table->string('hardware_signature', 64)->nullable()->after('signature');
            
            // Información del hospital en formato JSON
            $table->json('hospital_info')->nullable()->after('institution');
            
            // Información del hardware que activó la licencia
            $table->json('activation_hardware_info')->nullable()->after('activation_ip');
            
            // Número máximo de activaciones permitidas (por si en el futuro se permite multi-servidor)
            $table->integer('max_activations')->default(1)->after('is_active');
            
            // Índice para búsquedas por firma de hardware
            $table->index('hardware_signature');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropIndex(['hardware_signature']);
            $table->dropColumn([
                'hardware_signature',
                'hospital_info',
                'activation_hardware_info',
                'max_activations'
            ]);
        });
    }
};
