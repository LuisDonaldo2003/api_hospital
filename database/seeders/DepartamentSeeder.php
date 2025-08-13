<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartamentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['name' => 'Servicios Generales', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Farmacia', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Enfermeria', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Recursos Financieros', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Radiología y Ultrasonido', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Laboratorio de Patología Clínica', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Anestesiología/Cirugía', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Consulta Externa (Psicología)', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ginecólogo-Obstetrica', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Urgencias', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ingeniería y Mantenimiento. (Informática)', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ingeniería y Mantenimiento. (Jefe de Mantenimiento)', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Trabajo Social (Consulta Externa)', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Archivo Clínico', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cirugía', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Dirección', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Dietología (Jefe de Cocina)', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Trabajo Social (Violencia/Consulta Externa)', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Trabajo Social (Consulta Externa)', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pediatría', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Calidad (Enfermería)', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Medicina Interna', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Archivo Clínico', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Dietología', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pediatría (Médico General)', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sub Seccion 20 (Enfermería)', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Consulta Externa', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Recursos Financieros y Materiales (Activo Fijo)', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Toco-cirugía', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Recursos Humanos', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Enseñanza e Investigación (Enfermería)', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Enseñanza e Investigación', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sub Seccion 20', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Almacen (Enfermería)', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Enfermería (Área de Pediatría)', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Programa de Vigilancia y Epidemiología (Consulta Externa)', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Hospitalización-Medicina Interna', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Administrativo', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ingeniería y Mantenimiento', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Trabajo Social (Displasias/Consulta Externa)', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Recursos Financieros', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Consulta Externa VIH y Otras ITS (Psicología)', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Programa de Vigilancia y Epidemiología (Enfermería)', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Calidad', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Recursos Financieros y Materiales (Activo Fijo)', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Subdirección', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Trabajo Social Administrativo (Consulta Externa)', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Coordinación Médica', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],

        ];

        DB::table('departaments')->insert($data);
    }
}
