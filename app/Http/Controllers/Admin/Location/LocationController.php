<?php

namespace App\Http\Controllers\Admin\Location;

use App\Models\Location;
use App\Models\PriorityLocation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

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
        
        // Normalizar término de búsqueda (quitar acentos y convertir a minúsculas)
        $normalizedSearchTerm = $this->normalizeText($searchTerm);
        
        // Cache key para búsquedas frecuentes
        $cacheKey = 'location_search_' . md5($normalizedSearchTerm);
        
        // Intentar obtener desde cache primero (cachear por 5 minutos)
        $results = Cache::remember($cacheKey, 300, function () use ($searchTerm, $normalizedSearchTerm) {
            return $this->performOptimizedSearch($searchTerm, $normalizedSearchTerm);
        });

        return response()->json($results);
    }

    /**
     * Búsqueda optimizada usando la tabla de localidades prioritarias
     */
    private function performOptimizedSearch($searchTerm, $normalizedSearchTerm)
    {
        // Primero buscar en localidades prioritarias (más rápido)
        $priorityResults = PriorityLocation::where(function ($query) use ($searchTerm, $normalizedSearchTerm) {
                $query->where('location_name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('normalized_name', 'LIKE', "%{$normalizedSearchTerm}%");
            })
            ->orderBy('priority_level', 'asc')
            ->orderBy('location_name', 'asc')
            ->limit(15)
            ->get()
            ->map(function ($location) use ($searchTerm, $normalizedSearchTerm) {
                return [
                    'id' => $location->location_id,
                    'name' => $location->location_name,
                    'display_text' => $location->display_text,
                    'municipality_id' => $location->municipality_id,
                    'municipality_name' => $location->municipality_name,
                    'state_id' => $location->state_id,
                    'state_name' => $location->state_name,
                    'priority' => $location->priority_level,
                    'score' => $this->calculateRelevanceScore($location->location_name, $searchTerm, $normalizedSearchTerm, $location->priority_level),
                    'is_priority' => true
                ];
            });

        // Si tenemos suficientes resultados prioritarios, devolverlos
        if ($priorityResults->count() >= 10) {
            return $priorityResults->sortBy('score')->take(20)->values();
        }

        // Completar con búsqueda general si necesitamos más resultados
        $additionalResults = $this->searchNonPriorityLocations($searchTerm, $normalizedSearchTerm, 20 - $priorityResults->count());
        
        // Combinar resultados
        $allResults = $priorityResults->concat($additionalResults);
        
        return $allResults->sortBy('score')->take(20)->values();
    }

    /**
     * Búsqueda en localidades no prioritarias
     */
    private function searchNonPriorityLocations($searchTerm, $normalizedSearchTerm, $limit)
    {
        // Estados prioritarios para excluir de esta búsqueda
        $priorityStateIds = [12, 16, 15, 17, 21, 20]; // Guerrero, Michoacán, México, Morelos, Puebla, Oaxaca
        
        $locations = Location::with(['municipality.state'])
            ->whereHas('municipality.state', function ($query) use ($priorityStateIds) {
                $query->whereNotIn('id', $priorityStateIds);
            })
            ->where(function ($query) use ($searchTerm) {
                $query->where('name', 'LIKE', "%{$searchTerm}%");
            })
            ->select('id', 'name', 'municipality_id')
            ->limit($limit * 2) // Obtener más para filtrar después
            ->get()
            ->filter(function ($location) use ($searchTerm, $normalizedSearchTerm) {
                $locationNormalized = $this->normalizeText($location->name);
                
                // Búsqueda flexible
                return stripos($location->name, $searchTerm) !== false ||
                       stripos($locationNormalized, $normalizedSearchTerm) !== false ||
                       $this->similarityMatch($locationNormalized, $normalizedSearchTerm);
            })
            ->map(function ($location) use ($searchTerm, $normalizedSearchTerm) {
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
                    'priority' => 99, // Baja prioridad
                    'score' => $this->calculateRelevanceScore($location->name, $searchTerm, $normalizedSearchTerm, 99),
                    'is_priority' => false
                ];
            })
            ->take($limit);

        return $locations;
    }

    /**
     * Endpoint adicional para búsqueda rápida solo en estados prioritarios
     */
    public function searchPriorityOnly(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:2|max:100'
        ]);

        $searchTerm = trim($request->search);
        $normalizedSearchTerm = $this->normalizeText($searchTerm);
        
        $results = PriorityLocation::where(function ($query) use ($searchTerm, $normalizedSearchTerm) {
                $query->where('location_name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('normalized_name', 'LIKE', "%{$normalizedSearchTerm}%");
            })
            ->orderBy('priority_level', 'asc')
            ->orderBy('location_name', 'asc')
            ->limit(20)
            ->get()
            ->map(function ($location) {
                return [
                    'id' => $location->location_id,
                    'name' => $location->location_name,
                    'display_text' => $location->display_text,
                    'municipality_id' => $location->municipality_id,
                    'municipality_name' => $location->municipality_name,
                    'state_id' => $location->state_id,
                    'state_name' => $location->state_name,
                    'priority' => $location->priority_level
                ];
            });

        return response()->json($results);
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
