<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RoleFamily;

class RoleFamilySeeder extends Seeder
{
    public function run(): void
    {
        $families = [
            [
                'name' => 'Enseñanza',
                'description' => 'Familia de roles relacionados con enseñanza y evaluación',
            ],
            [
                'name' => 'Archivo',
                'description' => 'Familia de roles relacionados con gestión de archivo',
            ],
            [
                'name' => 'Recursos Humanos',
                'description' => 'Familia de roles relacionados con personal y contratos',
            ],
            [
                'name' => 'Administración',
                'description' => 'Familia de roles relacionados con administración del sistema',
            ],
        ];

        foreach ($families as $familyData) {
            RoleFamily::firstOrCreate(
                ['name' => $familyData['name']],
                $familyData
            );
        }
    }
}
