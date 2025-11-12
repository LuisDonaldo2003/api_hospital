<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeachingAreasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areas = [
            'MEDICINA',
            'ENFERMERIA',
            'MEDICO INTERNO DE PREGRADO',
            'ENFERMERO PASANTE DE SERVICIO SOCIAL',
            'ADMINISTRATIVA',
            'INFORMATICA',
        ];

        foreach ($areas as $area) {
            DB::table('teaching_areas')->updateOrInsert(
                ['nombre' => $area],
                [
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }
    }
}
