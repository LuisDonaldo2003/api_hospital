<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TeachingCatalogsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Este seeder SOLO crea el catálogo de Profesiones.
     * Las demás tablas tienen sus propios seeders:
     * - TeachingModalidadesSeeder
     * - TeachingParticipacionesSeeder
     * - TeachingAreasSeeder
     */
    public function run(): void
    {
        $now = Carbon::now();

        // ============================================
        // Catálogo de Profesiones
        // ============================================
        $profesiones = [
            ['nombre' => 'DR.', 'descripcion' => 'Doctor', 'activo' => true],
            ['nombre' => 'DRA.', 'descripcion' => 'Doctora', 'activo' => true],
            ['nombre' => 'MIP.', 'descripcion' => 'Médico Interno de Pregrado', 'activo' => true],
            ['nombre' => 'EPSS.', 'descripcion' => 'Enfermero Pasante de Servicio Social', 'activo' => true],
            ['nombre' => 'MDO.', 'descripcion' => 'Médico', 'activo' => true],
            ['nombre' => 'LE.', 'descripcion' => 'Licenciado en Enfermería', 'activo' => true],
            ['nombre' => 'L.E.', 'descripcion' => 'Licenciado en Enfermería', 'activo' => true],
            ['nombre' => 'E.L.E', 'descripcion' => 'Enfermero Licenciado en Enfermería', 'activo' => true],
            ['nombre' => 'ELE.', 'descripcion' => 'Enfermero Licenciado en Enfermería', 'activo' => true],
            ['nombre' => 'DAD.', 'descripcion' => 'Diplomado en Administración', 'activo' => true],
            ['nombre' => 'LIC.', 'descripcion' => 'Licenciado', 'activo' => true],
            ['nombre' => 'C.', 'descripcion' => 'Ciudadano', 'activo' => true],
            ['nombre' => 'LI.', 'descripcion' => 'Licenciado en Informática', 'activo' => true],
            ['nombre' => 'TR.', 'descripcion' => 'Trabajador', 'activo' => true],
            ['nombre' => 'L.Q.', 'descripcion' => 'Licenciado en Química', 'activo' => true],
        ];

        foreach ($profesiones as &$profesion) {
            $profesion['created_at'] = $now;
            $profesion['updated_at'] = $now;
        }

        foreach ($profesiones as $profesion) {
            DB::table('profesiones')->updateOrInsert(
                ['nombre' => $profesion['nombre']],
                $profesion
            );
        }

        $this->command->info('✓ 15 profesiones creadas/actualizadas');
    }
}
