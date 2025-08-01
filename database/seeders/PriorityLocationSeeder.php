<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PriorityLocationSeeder extends Seeder
{
    public function run(): void
    {
        // Estados prioritarios con sus niveles de prioridad
        $priorityStates = [
            12 => 1, // Guerrero - máxima prioridad
            16 => 2, // Michoacán - segunda prioridad
            15 => 3, // México - tercera prioridad
            17 => 4, // Morelos - cuarta prioridad
            21 => 5, // Puebla - quinta prioridad
            20 => 5, // Oaxaca - quinta prioridad
        ];

        // Obtener todas las localidades de estados prioritarios
        $locations = DB::table('locations')
            ->join('municipalities', 'locations.municipality_id', '=', 'municipalities.id')
            ->join('states', 'municipalities.state_id', '=', 'states.id')
            ->whereIn('states.id', array_keys($priorityStates))
            ->select(
                'locations.id as location_id',
                'locations.name as location_name',
                'municipalities.id as municipality_id',
                'municipalities.name as municipality_name',
                'states.id as state_id',
                'states.name as state_name'
            )
            ->get();

        $priorityLocations = [];
        foreach ($locations as $location) {
            $priorityLevel = $priorityStates[$location->state_id] ?? 5;
            $displayText = $location->location_name . ' - ' . $location->municipality_name . ', ' . $location->state_name;
            $normalizedName = $this->normalizeText($location->location_name);

            $priorityLocations[] = [
                'location_id' => $location->location_id,
                'municipality_id' => $location->municipality_id,
                'state_id' => $location->state_id,
                'location_name' => $location->location_name,
                'municipality_name' => $location->municipality_name,
                'state_name' => $location->state_name,
                'display_text' => $displayText,
                'normalized_name' => $normalizedName,
                'priority_level' => $priorityLevel,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // Insertar en chunks para evitar problemas de memoria
        foreach (array_chunk($priorityLocations, 1000) as $chunk) {
            DB::table('priority_locations')->insert($chunk);
        }
    }

    /**
     * Normaliza texto quitando acentos y convirtiendo a minúsculas
     */
    private function normalizeText($text)
    {
        $text = strtolower(trim($text));
        $replacements = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ñ' => 'n', 'ü' => 'u', 'ç' => 'c',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
            'ã' => 'a', 'õ' => 'o', 'ä' => 'a', 'ë' => 'e', 'ï' => 'i',
            'ö' => 'o', 'ű' => 'u', 'Á' => 'a', 'É' => 'e', 'Í' => 'i',
            'Ó' => 'o', 'Ú' => 'u', 'Ñ' => 'n', 'Ü' => 'u', 'Ç' => 'c'
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
}
