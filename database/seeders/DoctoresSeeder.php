<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DoctoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $doctors = [
            // Oftalmología (ID: 1) - 11:00 AM
            [
                'nombre_completo' => 'Dra. Ana María González López',
                'especialidad_id' => 1,
                'turno' => 'Matutino',
                'hora_inicio_matutino' => '11:00',
                'hora_fin_matutino' => '14:00',
                'hora_inicio_vespertino' => null,
                'hora_fin_vespertino' => null,
                'activo' => true,
            ],
            
            // Medicina Interna (ID: 2) - 8:00 AM a 1:00 PM
            [
                'nombre_completo' => 'Dr. Carlos Eduardo Martínez Ruiz',
                'especialidad_id' => 2,
                'turno' => 'Matutino',
                'hora_inicio_matutino' => '08:00',
                'hora_fin_matutino' => '13:00',
                'hora_inicio_vespertino' => null,
                'hora_fin_vespertino' => null,
                'activo' => true,
            ],
            
            // Otorrinolaringología (ID: 3) - 8:00 AM a 1:00 PM
            [
                'nombre_completo' => 'Dr. Roberto Alejandro Pérez Sánchez',
                'especialidad_id' => 3,
                'turno' => 'Matutino',
                'hora_inicio_matutino' => '08:00',
                'hora_fin_matutino' => '13:00',
                'hora_inicio_vespertino' => null,
                'hora_fin_vespertino' => null,
                'activo' => true,
            ],
            
            // Nutrición (ID: 4) - Horario pendiente
            [
                'nombre_completo' => 'Dra. María Elena Ramírez Torres',
                'especialidad_id' => 4,
                'turno' => 'Matutino',
                'hora_inicio_matutino' => '09:00',
                'hora_fin_matutino' => '14:00',
                'hora_inicio_vespertino' => null,
                'hora_fin_vespertino' => null,
                'activo' => true,
            ],
            
            // Psicología (ID: 5) - Horario pendiente
            [
                'nombre_completo' => 'Lic. Laura Patricia Hernández Silva',
                'especialidad_id' => 5,
                'turno' => 'Mixto',
                'hora_inicio_matutino' => '09:00',
                'hora_fin_matutino' => '13:00',
                'hora_inicio_vespertino' => '15:00',
                'hora_fin_vespertino' => '19:00',
                'activo' => true,
            ],
            
            // Cirugía General (ID: 6) - Horario pendiente
            [
                'nombre_completo' => 'Dr. Fernando José Morales García',
                'especialidad_id' => 6,
                'turno' => 'Matutino',
                'hora_inicio_matutino' => '07:00',
                'hora_fin_matutino' => '13:00',
                'hora_inicio_vespertino' => null,
                'hora_fin_vespertino' => null,
                'activo' => true,
            ],
            
            // Pediatría (ID: 7) - Horario pendiente
            [
                'nombre_completo' => 'Dra. Gabriela Fernanda López Cruz',
                'especialidad_id' => 7,
                'turno' => 'Mixto',
                'hora_inicio_matutino' => '09:00',
                'hora_fin_matutino' => '13:00',
                'hora_inicio_vespertino' => '15:00',
                'hora_fin_vespertino' => '19:00',
                'activo' => true,
            ],
            
            // Traumatología (ID: 8) - Horario pendiente
            [
                'nombre_completo' => 'Dr. Miguel Ángel Rodríguez Vega',
                'especialidad_id' => 8,
                'turno' => 'Mixto',
                'hora_inicio_matutino' => '08:00',
                'hora_fin_matutino' => '12:00',
                'hora_inicio_vespertino' => '16:00',
                'hora_fin_vespertino' => '20:00',
                'activo' => true,
            ],
        ];

        foreach ($doctors as $doctor) {
            DB::table('doctors')->insert([
                ...$doctor,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
