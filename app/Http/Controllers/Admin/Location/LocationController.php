<?php

namespace App\Http\Controllers\Admin\Location;

use App\Models\Location;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LocationController extends Controller
{
    public function byMunicipality(Request $request)
    {
        $request->validate(['municipality_id' => 'required|integer|exists:municipalities,id']);

        return response()->json(
            Location::where('municipality_id', $request->municipality_id)
                ->select('id', 'name', 'municipality_id')
                ->orderBy('name')
                ->get()
        );
    }

    public function searchByName(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:2|max:100'
        ]);

        $searchTerm = trim($request->search);
        
        // Estados prioritarios (Guerrero y estados colindantes)
        $priorityStates = [
            'Guerrero' => 1,
            'Michoacán' => 2,
            'México' => 3,
            'Morelos' => 4,
            'Puebla' => 5,
            'Oaxaca' => 6
        ];

        // Normalizar término de búsqueda (quitar acentos y convertir a minúsculas)
        $normalizedSearchTerm = $this->normalizeText($searchTerm);
        
        // Obtener todas las localidades que podrían coincidir
        $locations = Location::with(['municipality.state'])
            ->select('id', 'name', 'municipality_id')
            ->get()
            ->filter(function ($location) use ($searchTerm, $normalizedSearchTerm) {
                $locationName = $location->name;
                $locationNormalized = $this->normalizeText($locationName);
                
                // Búsqueda flexible: exacta, contiene, o sin acentos
                return stripos($locationName, $searchTerm) !== false ||
                       stripos($locationNormalized, $normalizedSearchTerm) !== false ||
                       $this->similarityMatch($locationNormalized, $normalizedSearchTerm);
            })
            ->map(function ($location) use ($priorityStates, $searchTerm, $normalizedSearchTerm) {
                $stateName = $location->municipality->state->name;
                $priority = $priorityStates[$stateName] ?? 99;
                
                // Calcular score de relevancia
                $score = $this->calculateRelevanceScore($location->name, $searchTerm, $normalizedSearchTerm, $priority);
                
                return [
                    'id' => $location->id,
                    'name' => $location->name,
                    'display_text' => $location->name . ' - ' . 
                                   $location->municipality->name . ', ' . 
                                   $location->municipality->state->name,
                    'municipality_id' => $location->municipality_id,
                    'municipality_name' => $location->municipality->name,
                    'state_id' => $location->municipality->state->id,
                    'state_name' => $location->municipality->state->name,
                    'score' => $score,
                    'priority' => $priority
                ];
            })
            // Ordenar por score (menor score = mayor relevancia)
            ->sortBy('score')
            ->take(20)
            ->values();

        return response()->json($locations);
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

    /**
     * Verifica si dos strings son similares usando algoritmo de similitud
     */
    private function similarityMatch($text1, $text2)
    {
        // Si uno de los strings está vacío, no hay similitud
        if (empty($text1) || empty($text2)) {
            return false;
        }

        // Calcular similitud usando Levenshtein
        $distance = levenshtein($text1, $text2);
        $maxLength = max(strlen($text1), strlen($text2));
        
        if ($maxLength === 0) {
            return true;
        }
        
        $similarity = 1 - ($distance / $maxLength);
        
        // Considerar como similar si tienen al menos 70% de similitud
        return $similarity >= 0.7;
    }

    /**
     * Calcula score de relevancia para ordenar resultados
     * Menor score = mayor relevancia
     */
    private function calculateRelevanceScore($locationName, $searchTerm, $normalizedSearchTerm, $statePriority)
    {
        $locationNormalized = $this->normalizeText($locationName);
        $searchNormalized = $normalizedSearchTerm;
        
        $score = $statePriority * 1000; // Base score por prioridad de estado
        
        // Coincidencia exacta (mejor score)
        if (strtolower($locationName) === strtolower($searchTerm)) {
            return $score + 1;
        }
        
        // Coincidencia exacta sin acentos
        if ($locationNormalized === $searchNormalized) {
            return $score + 2;
        }
        
        // Empieza con el término buscado
        if (stripos($locationName, $searchTerm) === 0) {
            return $score + 3;
        }
        
        // Empieza con el término buscado (sin acentos)
        if (strpos($locationNormalized, $searchNormalized) === 0) {
            return $score + 4;
        }
        
        // Contiene el término completo
        if (stripos($locationName, $searchTerm) !== false) {
            return $score + 5;
        }
        
        // Contiene el término completo (sin acentos)
        if (strpos($locationNormalized, $searchNormalized) !== false) {
            return $score + 6;
        }
        
        // Similitud usando Levenshtein distance (para errores de escritura)
        if ($this->similarityMatch($locationNormalized, $searchNormalized)) {
            $distance = levenshtein($locationNormalized, $searchNormalized);
            $maxLength = max(strlen($locationNormalized), strlen($searchNormalized));
            
            if ($maxLength > 0) {
                $similarity = 1 - ($distance / $maxLength);
                return $score + 7 + (int)((1 - $similarity) * 10);
            }
        }
        
        // Score muy alto para resultados poco relevantes
        return $score + 100;
    }
}
