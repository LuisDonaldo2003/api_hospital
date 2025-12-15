<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EspecialidadesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $especialidades = [
            ['nombre' => 'Nutrición', 'descripcion' => 'Especialidad en alimentación y nutrición', 'activo' => true],
            ['nombre' => 'Psicología', 'descripcion' => 'Especialidad en salud mental y comportamiento', 'activo' => true],
            ['nombre' => 'Cirugía General', 'descripcion' => 'Especialidad en procedimientos quirúrgicos', 'activo' => true],
            ['nombre' => 'Pediatría', 'descripcion' => 'Especialidad en salud infantil', 'activo' => true],
            ['nombre' => 'Traumatología', 'descripcion' => 'Especialidad en huesos y articulaciones', 'activo' => true],
        ];

        foreach ($especialidades as $especialidad) {
            DB::table('especialidades')->insert([
                'nombre' => $especialidad['nombre'],
                'descripcion' => $especialidad['descripcion'],
                'activo' => $especialidad['activo'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
