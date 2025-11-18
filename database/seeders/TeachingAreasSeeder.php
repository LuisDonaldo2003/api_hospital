<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeachingAreasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Puebla el catálogo de Áreas para el módulo de Teaching
     */
    public function run(): void
    {
        $areas = [
            ['nombre' => 'MEDICINA', 'descripcion' => 'Área médica'],
            ['nombre' => 'ENFERMERIA', 'descripcion' => 'Área de enfermería'],
            ['nombre' => 'MEDICO INTERNO DE PREGRADO', 'descripcion' => 'Estudiantes de medicina en práctica'],
            ['nombre' => 'ENFERMERO PASANTE DE SERVICIO SOCIAL', 'descripcion' => 'Estudiantes de enfermería en servicio social'],
            ['nombre' => 'ADMINISTRATIVA', 'descripcion' => 'Área administrativa'],
            ['nombre' => 'INFORMATICA', 'descripcion' => 'Área de tecnología e informática'],
        ];

        foreach ($areas as $area) {
            DB::table('areas')->updateOrInsert(
                ['nombre' => $area['nombre']],
                [
                    'nombre' => $area['nombre'],
                    'descripcion' => $area['descripcion'],
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }
        
        $this->command->info('✓ 6 áreas creadas/actualizadas');
    }
}
