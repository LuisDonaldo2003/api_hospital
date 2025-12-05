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
            ['nombre' => 'Medicina General', 'descripcion' => 'Atención médica general', 'activo' => true],
            ['nombre' => 'Pediatría', 'descripcion' => 'Especialidad en salud infantil', 'activo' => true],
            ['nombre' => 'Cardiología', 'descripcion' => 'Especialidad en corazón y sistema cardiovascular', 'activo' => true],
            ['nombre' => 'Dermatología', 'descripcion' => 'Especialidad en piel y sus enfermedades', 'activo' => true],
            ['nombre' => 'Ginecología', 'descripcion' => 'Especialidad en salud femenina', 'activo' => true],
            ['nombre' => 'Traumatología', 'descripcion' => 'Especialidad en huesos y articulaciones', 'activo' => true],
            ['nombre' => 'Oftalmología', 'descripcion' => 'Especialidad en ojos y visión', 'activo' => true],
            ['nombre' => 'Neurología', 'descripcion' => 'Especialidad en sistema nervioso', 'activo' => true],
            ['nombre' => 'Psiquiatría', 'descripcion' => 'Especialidad en salud mental', 'activo' => true],
            ['nombre' => 'Endocrinología', 'descripcion' => 'Especialidad en hormonas y metabolismo', 'activo' => true],
            ['nombre' => 'Gastroenterología', 'descripcion' => 'Especialidad en sistema digestivo', 'activo' => true],
            ['nombre' => 'Neumología', 'descripcion' => 'Especialidad en pulmones y sistema respiratorio', 'activo' => true],
            ['nombre' => 'Urología', 'descripcion' => 'Especialidad en sistema urinario', 'activo' => true],
            ['nombre' => 'Otorrinolaringología', 'descripcion' => 'Especialidad en oído, nariz y garganta', 'activo' => true],
            ['nombre' => 'Oncología', 'descripcion' => 'Especialidad en cáncer', 'activo' => true],
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
