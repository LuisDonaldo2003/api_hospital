<?php

namespace Database\Seeders;

use App\Models\AppointmentService;
use Illuminate\Database\Seeder;

class AppointmentServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            // Consulta de Especialidades
            ['nombre' => 'Cirugía General', 'categoria' => 'Especialidad', 'orden' => 1],
            ['nombre' => 'Traumatología y Ortopedia', 'categoria' => 'Especialidad', 'orden' => 2],
            ['nombre' => 'Urología', 'categoria' => 'Especialidad', 'orden' => 3],
            ['nombre' => 'Neurología', 'categoria' => 'Especialidad', 'orden' => 4],
            ['nombre' => 'Oftalmología', 'categoria' => 'Especialidad', 'orden' => 5],
            ['nombre' => 'Otorrinolaringología', 'categoria' => 'Especialidad', 'orden' => 6],
            ['nombre' => 'Medicina Interna', 'categoria' => 'Especialidad', 'orden' => 7],
            ['nombre' => 'Ginecología y Obstetricia', 'categoria' => 'Especialidad', 'orden' => 8],
            ['nombre' => 'Pediatría', 'categoria' => 'Especialidad', 'orden' => 9],
            ['nombre' => 'Dentistas', 'categoria' => 'Especialidad', 'orden' => 10],

            // Otros Servicios
            ['nombre' => 'Nutrición', 'categoria' => 'Otros', 'orden' => 11],
            ['nombre' => 'Psicología', 'categoria' => 'Otros', 'orden' => 12],
            ['nombre' => 'Clínica de Displasias', 'categoria' => 'Otros', 'orden' => 13],
            ['nombre' => 'SAIH', 'categoria' => 'Otros', 'orden' => 14],
            ['nombre' => 'Medicina Preventiva', 'categoria' => 'Otros', 'orden' => 15],
            ['nombre' => 'Planificación Familiar', 'categoria' => 'Otros', 'orden' => 16],

            // Consulta General
            ['nombre' => 'Médico General 1', 'categoria' => 'General', 'orden' => 17],
            ['nombre' => 'Médico General 2', 'categoria' => 'General', 'orden' => 18],
        ];

        foreach ($services as $service) {
            AppointmentService::updateOrCreate(
                ['nombre' => $service['nombre']], // Buscar por nombre
                array_merge($service, ['activo' => true, 'descripcion' => null]) // Actualizar/Crear
            );
        }

        $this->command->info('✅ 18 servicios de citas creados/actualizados correctamente');
    }
}
