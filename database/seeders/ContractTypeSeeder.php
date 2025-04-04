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
        ];

        DB::table('contract_types')->insert($data);
    }
}
