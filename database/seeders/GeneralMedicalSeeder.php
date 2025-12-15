<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GeneralMedicalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            ['nombre' => 'Oftalmología', 'descripcion' => 'Consulta y atención de enfermedades oculares', 'activo' => true],
            ['nombre' => 'Medicina Interna', 'descripcion' => 'Atención integral de adultos y enfermedades complejas', 'activo' => true],
            ['nombre' => 'Otorrinolaringología', 'descripcion' => 'Atención de oído, nariz y garganta', 'activo' => true],
        ];

        foreach ($categorias as $categoria) {
            DB::table('general_medicals')->insert([
                'nombre' => $categoria['nombre'],
                'descripcion' => $categoria['descripcion'],
                'activo' => $categoria['activo'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
