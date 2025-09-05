<?php

namespace Database\Seeders;

use App\Models\Personal;
use Illuminate\Database\Seeder;

class PersonalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $personales = [
            [
                'nombre' => 'Juan Carlos',
                'apellidos' => 'García López',
                'tipo' => 'Clínico',
                'fecha_ingreso' => '2023-01-15',
                'activo' => true,
            ],
            [
                'nombre' => 'María Elena',
                'apellidos' => 'Rodríguez Sánchez',
                'tipo' => 'No Clínico',
                'fecha_ingreso' => '2022-06-10',
                'activo' => true,
            ],
            [
                'nombre' => 'Carlos Alberto',
                'apellidos' => 'Martínez Pérez',
                'tipo' => 'Clínico',
                'fecha_ingreso' => '2023-03-20',
                'activo' => true,
            ],
            [
                'nombre' => 'Ana Patricia',
                'apellidos' => 'López Fernández',
                'tipo' => 'Clínico',
                'fecha_ingreso' => '2021-08-05',
                'activo' => true,
            ],
            [
                'nombre' => 'Miguel Angel',
                'apellidos' => 'Hernández Vásquez',
                'tipo' => 'No Clínico',
                'fecha_ingreso' => '2022-11-12',
                'activo' => true,
            ]
        ];

        foreach ($personales as $personal) {
            Personal::create($personal);
        }
    }
}
