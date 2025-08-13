<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GenderSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('genders')->insert([
            ['name' => 'Hombre'],
            ['name' => 'Mujer'],
            ['name' => 'Otro'],
            ['name' => 'No especificado'],
        ]);
    }
}
