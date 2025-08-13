<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('archive', function (Blueprint $table) {
            $table->string('location_text', 100)->nullable()->after('location_id')
                  ->comment('Texto plano de localidad cuando no se encuentra en la BD');
        });
    }

    public function down(): void
    {
        Schema::table('archive', function (Blueprint $table) {
            $table->dropColumn('location_text');
        });
    }
};
