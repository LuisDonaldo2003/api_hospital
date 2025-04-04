<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfileSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['name' => 'Tecnico en informatica', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Licenciatura en literatura', 'state' => 1, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('profiles')->insert($data);
    }
}
