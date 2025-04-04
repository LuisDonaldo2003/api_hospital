<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FunctionSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['name' => 'Apoyo administrativo', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Secretaria', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('functions')->insert($data);
    }
}
