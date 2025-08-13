<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class StateSeeder extends Seeder
{
    public function run(): void
    {
        $states = [
            ['id' => 1, 'name' => 'Aguascalientes', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Baja California', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Baja California Sur', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Campeche', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Coahuila de Zaragoza', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Colima', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'Chiapas', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => 'Chihuahua', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'name' => 'Ciudad de México', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'name' => 'Durango', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'Guanajuato', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'name' => 'Guerrero', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 13, 'name' => 'Hidalgo', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 14, 'name' => 'Jalisco', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 15, 'name' => 'México', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 16, 'name' => 'Michoacán de Ocampo', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 17, 'name' => 'Morelos', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 18, 'name' => 'Nayarit', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 19, 'name' => 'Nuevo León', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 20, 'name' => 'Oaxaca', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 21, 'name' => 'Puebla', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 22, 'name' => 'Querétaro', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 23, 'name' => 'Quintana Roo', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 24, 'name' => 'San Luis Potosí', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 25, 'name' => 'Sinaloa', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 26, 'name' => 'Sonora', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 27, 'name' => 'Tabasco', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 28, 'name' => 'Tamaulipas', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 29, 'name' => 'Tlaxcala', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 30, 'name' => 'Veracruz de Ignacio de la Llave', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 31, 'name' => 'Yucatán', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 32, 'name' => 'Zacatecas', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 33, 'name' => 'Chihuahua-Durango', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 34, 'name' => 'Chiapas-Oaxaca', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 35, 'name' => 'Campeche-Quintana Roo', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 36, 'name' => 'Chiapas-Tabasco', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 37, 'name' => 'Campeche-Yucatán', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 38, 'name' => 'Quintana Roo-Yucatán', 'country_id' => 1, 'created_at' => now(), 'updated_at' => now()],
        ];
        foreach (array_chunk($states, 1000) as $chunk) {
            DB::table('states')->insert($chunk);
        }
    }
}