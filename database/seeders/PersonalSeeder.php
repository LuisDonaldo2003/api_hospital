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
                'rfc' => 'GALJ850315HDF',
                'numero_checador' => '1001',
                'fecha_ingreso' => '2023-01-15',
                'activo' => true,
            ],
            [
                'nombre' => 'María Elena',
                'apellidos' => 'Rodríguez Sánchez',
                'tipo' => 'No Clínico',
                'rfc' => 'ROSM900610MDF',
                'numero_checador' => '1002',
                'fecha_ingreso' => '2022-06-10',
                'activo' => true,
            ],
            [
                'nombre' => 'Carlos Alberto',
                'apellidos' => 'Martínez Pérez',
                'tipo' => 'Clínico',
                'rfc' => 'MAPC880320HDF',
                'numero_checador' => '1003',
                'fecha_ingreso' => '2023-03-20',
                'activo' => true,
            ],
            [
                'nombre' => 'Ana Patricia',
                'apellidos' => 'López Fernández',
                'tipo' => 'Clínico',
                'rfc' => 'LOFA920805MDF',
                'numero_checador' => '1004',
                'fecha_ingreso' => '2021-08-05',
                'activo' => true,
            ],
            [
                'nombre' => 'Miguel Angel',
                'apellidos' => 'Hernández Vásquez',
                'tipo' => 'No Clínico',
                'rfc' => 'HEVM870112HDF',
                'numero_checador' => '1005',
                'fecha_ingreso' => '2022-11-12',
                'activo' => true,
            ],
            [
                'nombre' => 'Luisa Fernanda',
                'apellidos' => 'Torres Ramírez',
                'tipo' => 'Clínico',
                'rfc' => 'TORL950225MDF',
                'numero_checador' => '1006',
                'fecha_ingreso' => '2023-07-18',
                'activo' => true,
            ],
            [
                'nombre' => 'Roberto Carlos',
                'apellidos' => 'Jiménez Cruz',
                'tipo' => 'No Clínico',
                'rfc' => 'JICR860918HDF',
                'numero_checador' => '1007',
                'fecha_ingreso' => '2022-09-30',
                'activo' => true,
            ]
        ];

        foreach ($personales as $personal) {
            Personal::create($personal);
        }
    }
}
