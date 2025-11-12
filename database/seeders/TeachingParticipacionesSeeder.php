<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeachingParticipacionesSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['nombre' => 'ASISTENTE'],
            ['nombre' => 'PONENTE'],
            ['nombre' => 'ORGANIZADORA'],
            ['nombre' => 'ORGANIZADOR'],
            ['nombre' => 'COORDINADORA'],
            ['nombre' => 'COORDINADOR'],
            ['nombre' => 'ASESOR'],
            ['nombre' => 'PROF.HONOR.'],
        ];

        foreach ($items as $it) {
            DB::table('participaciones')->updateOrInsert(['nombre' => $it['nombre']], $it + ['activo' => 1]);
        }
    }
}
