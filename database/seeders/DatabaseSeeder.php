<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    $this->call([
        DepartamentSeeder::class,
        ProfileSeeder::class,
        ContractTypeSeeder::class,
        PermissionsDemoSeeder::class,
        GenderSeeder::class,
        CountrySeeder::class,
        StateSeeder::class,
        MunicipalitySeeder::class,
        LocationSeeder::class,
        ArchiveSeeder::class,
        PriorityLocationSeeder::class,
    ]);
}

}
