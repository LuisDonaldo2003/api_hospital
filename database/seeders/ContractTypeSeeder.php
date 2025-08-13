<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContractTypeSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['name' => 'Sin cedula', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Draft', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'INSABI', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Homologado', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Guardias', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Regularizado', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'IMSS Bienestar', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Formalizado 1', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Formalizado 2', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Formalizado 3', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cuotas de Recuperación´', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Base', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Eventual', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Confianza', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],


        ];

        DB::table('contract_types')->insert($data);
    }
}
