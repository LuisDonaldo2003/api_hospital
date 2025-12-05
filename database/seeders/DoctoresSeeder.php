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
            // Medicina General
            [
                'nombre_completo' => 'Dr. Juan Carlos Pérez García',
                'especialidad_id' => 1,
                'turno' => 'Matutino',
                'hora_inicio_matutino' => '08:00',
                'hora_fin_matutino' => '14:00',
                'hora_inicio_vespertino' => null,
                'hora_fin_vespertino' => null,
                'activo' => true,
            ],
            [
                'nombre_completo' => 'Dra. Laura Patricia Mendoza Silva',
                'especialidad_id' => 1,
                'turno' => 'Vespertino',
                'hora_inicio_matutino' => null,
                'hora_fin_matutino' => null,
                'hora_inicio_vespertino' => '14:00',
                'hora_fin_vespertino' => '20:00',
                'activo' => true,
            ],
            
            // Pediatría
            [
                'nombre_completo' => 'Dra. María Elena González López',
                'especialidad_id' => 2,
                'turno' => 'Mixto',
                'hora_inicio_matutino' => '09:00',
                'hora_fin_matutino' => '13:00',
                'hora_inicio_vespertino' => '15:00',
                'hora_fin_vespertino' => '19:00',
                'activo' => true,
            ],
            [
                'nombre_completo' => 'Dr. Roberto Alejandro Flores Ruiz',
                'especialidad_id' => 2,
                'turno' => 'Matutino',
                'hora_inicio_matutino' => '07:00',
                'hora_fin_matutino' => '13:00',
                'hora_inicio_vespertino' => null,
                'hora_fin_vespertino' => null,
                'activo' => true,
            ],
            
            // Cardiología
            [
                'nombre_completo' => 'Dr. Carlos Eduardo Rodríguez Martínez',
                'especialidad_id' => 3,
                'turno' => 'Matutino',
                'hora_inicio_matutino' => '08:00',
                'hora_fin_matutino' => '14:00',
                'hora_inicio_vespertino' => null,
                'hora_fin_vespertino' => null,
                'activo' => true,
            ],
            [
                'nombre_completo' => 'Dra. Patricia Ivonne Sánchez Vargas',
                'especialidad_id' => 3,
                'turno' => 'Vespertino',
                'hora_inicio_matutino' => null,
                'hora_fin_matutino' => null,
                'hora_inicio_vespertino' => '14:00',
                'hora_fin_vespertino' => '20:00',
                'activo' => true,
            ],
            
            // Dermatología
            [
                'nombre_completo' => 'Dra. Ana Sofía Hernández Sánchez',
                'especialidad_id' => 4,
                'turno' => 'Matutino',
                'hora_inicio_matutino' => '07:00',
                'hora_fin_matutino' => '13:00',
                'hora_inicio_vespertino' => null,
                'hora_fin_vespertino' => null,
                'activo' => true,
            ],
            [
                'nombre_completo' => 'Dr. Miguel Ángel Castro Morales',
                'especialidad_id' => 4,
                'turno' => 'Mixto',
                'hora_inicio_matutino' => '10:00',
                'hora_fin_matutino' => '14:00',
                'hora_inicio_vespertino' => '16:00',
                'hora_fin_vespertino' => '20:00',
                'activo' => true,
            ],
            
            // Ginecología
            [
                'nombre_completo' => 'Dra. Gabriela Fernanda Ramírez Torres',
                'especialidad_id' => 5,
                'turno' => 'Vespertino',
                'hora_inicio_matutino' => null,
                'hora_fin_matutino' => null,
                'hora_inicio_vespertino' => '15:00',
                'hora_fin_vespertino' => '21:00',
                'activo' => true,
            ],
            [
                'nombre_completo' => 'Dr. Fernando José Ortiz Gutiérrez',
                'especialidad_id' => 5,
                'turno' => 'Matutino',
                'hora_inicio_matutino' => '08:00',
                'hora_fin_matutino' => '14:00',
                'hora_inicio_vespertino' => null,
                'hora_fin_vespertino' => null,
                'activo' => true,
            ],
            
            // Oftalmología
            [
                'nombre_completo' => 'Dr. Ricardo Javier Moreno Delgado',
                'especialidad_id' => 6,
                'turno' => 'Matutino',
                'hora_inicio_matutino' => '07:00',
                'hora_fin_matutino' => '13:00',
                'hora_inicio_vespertino' => null,
                'hora_fin_vespertino' => null,
                'activo' => true,
            ],
            [
                'nombre_completo' => 'Dra. Daniela Margarita López Cruz',
                'especialidad_id' => 6,
                'turno' => 'Vespertino',
                'hora_inicio_matutino' => null,
                'hora_fin_matutino' => null,
                'hora_inicio_vespertino' => '14:00',
                'hora_fin_vespertino' => '20:00',
                'activo' => true,
            ],
            
            // Traumatología
            [
                'nombre_completo' => 'Dr. Sergio Alberto Vega Jiménez',
                'especialidad_id' => 7,
                'turno' => 'Mixto',
                'hora_inicio_matutino' => '08:00',
                'hora_fin_matutino' => '12:00',
                'hora_inicio_vespertino' => '16:00',
                'hora_fin_vespertino' => '20:00',
                'activo' => true,
            ],
            [
                'nombre_completo' => 'Dra. Mónica Alejandra Reyes Campos',
                'especialidad_id' => 7,
                'turno' => 'Vespertino',
                'hora_inicio_matutino' => null,
                'hora_fin_matutino' => null,
                'hora_inicio_vespertino' => '14:00',
                'hora_fin_vespertino' => '20:00',
                'activo' => true,
            ],
            
            // Neurología
            [
                'nombre_completo' => 'Dr. Andrés Felipe Navarro Espinoza',
                'especialidad_id' => 8,
                'turno' => 'Matutino',
                'hora_inicio_matutino' => '08:00',
                'hora_fin_matutino' => '14:00',
                'hora_inicio_vespertino' => null,
                'hora_fin_vespertino' => null,
                'activo' => true,
            ],
            [
                'nombre_completo' => 'Dra. Verónica Isabel Aguilar Mendoza',
                'especialidad_id' => 8,
                'turno' => 'Vespertino',
                'hora_inicio_matutino' => null,
                'hora_fin_matutino' => null,
                'hora_inicio_vespertino' => '15:00',
                'hora_fin_vespertino' => '21:00',
                'activo' => true,
            ],
            
            // Psiquiatría
            [
                'nombre_completo' => 'Dra. Carmen Leticia Torres Ríos',
                'especialidad_id' => 9,
                'turno' => 'Mixto',
                'hora_inicio_matutino' => '09:00',
                'hora_fin_matutino' => '13:00',
                'hora_inicio_vespertino' => '15:00',
                'hora_fin_vespertino' => '19:00',
                'activo' => true,
            ],
            [
                'nombre_completo' => 'Dr. Héctor Manuel Domínguez Peña',
                'especialidad_id' => 9,
                'turno' => 'Matutino',
                'hora_inicio_matutino' => '07:00',
                'hora_fin_matutino' => '13:00',
                'hora_inicio_vespertino' => null,
                'hora_fin_vespertino' => null,
                'activo' => true,
            ],
            
            // Medicina Interna
            [
                'nombre_completo' => 'Dr. Francisco Javier Medina Guerrero',
                'especialidad_id' => 10,
                'turno' => 'Vespertino',
                'hora_inicio_matutino' => null,
                'hora_fin_matutino' => null,
                'hora_inicio_vespertino' => '14:00',
                'hora_fin_vespertino' => '20:00',
                'activo' => true,
            ],
            [
                'nombre_completo' => 'Dra. Silvia Rocío Paredes Luna',
                'especialidad_id' => 10,
                'turno' => 'Matutino',
                'hora_inicio_matutino' => '08:00',
                'hora_fin_matutino' => '14:00',
                'hora_inicio_vespertino' => null,
                'hora_fin_vespertino' => null,
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
