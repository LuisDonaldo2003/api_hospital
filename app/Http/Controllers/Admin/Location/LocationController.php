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
        $normalizedSearchTerm = $this->normalizeText($searchTerm);
        $cleanedSearchTerm = $this->cleanCommonWords($normalizedSearchTerm);

        // Buscar en localidades prioritarias (LIKE y normalización)
        $priorityResults = PriorityLocation::whereRaw('LOWER(normalized_name) like ?', ["%" . strtolower($cleanedSearchTerm) . "%"])
            ->orWhereRaw('LOWER(location_name) like ?', ["%" . strtolower($searchTerm) . "%"])
            ->limit(20)
            ->get();

        // Si hay menos de 20 resultados, buscar en todas las localidades (fuzzy, iniciales, etc.)
        if ($priorityResults->count() < 20) {
            $priorityIds = $priorityResults->pluck('location_id')->toArray();

            $additionalResults = Location::with(['municipality.state'])
                ->whereNotIn('id', $priorityIds)
                ->get()
                ->filter(function ($location) use ($searchTerm, $normalizedSearchTerm, $cleanedSearchTerm) {
                    $locationNormalized = $this->normalizeText($location->name);
                    $locationCleaned = $this->cleanCommonWords($locationNormalized);

                    // Coincidencia exacta o por LIKE
                    if (
                        stripos($location->name, $searchTerm) !== false ||
                        stripos($locationNormalized, $normalizedSearchTerm) !== false ||
                        stripos($locationCleaned, $cleanedSearchTerm) !== false
                    ) {
                        return true;
                    }

                    // Coincidencia por Levenshtein (fuzzy)
                    $distance = levenshtein($cleanedSearchTerm, $locationCleaned);
                    if ($distance <= 2) { // Puedes ajustar el umbral
                        return true;
                    }

                    // Coincidencia por iniciales
                    $searchInitials = implode('', array_map(fn($w) => $w[0] ?? '', explode(' ', $cleanedSearchTerm)));
                    $locationInitials = implode('', array_map(fn($w) => $w[0] ?? '', explode(' ', $locationCleaned)));
                    if ($searchInitials === $locationInitials && strlen($searchInitials) > 1) {
                        return true;
                    }

                    // Alias manuales (puedes expandir este array según tus necesidades)
                    $alias = [
                        'jario pantoja' => 'jario y pantoja',
                        'el guayavo' => 'el guayabo',
                        // ...otros casos frecuentes...
                    ];
                    if (isset($alias[$cleanedSearchTerm]) && $locationCleaned === $this->normalizeText($alias[$cleanedSearchTerm])) {
                        return true;
                    }

                    return false;
                })
                ->take(20 - $priorityResults->count())
                ->map(function ($location) {
                    return [
                        'id' => $location->id,
                        'name' => $location->name,
                        'display_text' => $location->name . ' - ' . $location->municipality->name . ', ' . $location->municipality->state->name,
                        'municipality_id' => $location->municipality->id,
                        'municipality_name' => $location->municipality->name,
                        'state_id' => $location->municipality->state->id,
                        'state_name' => $location->municipality->state->name,
                    ];
                });

            $results = $priorityResults->map(function ($item) {
                return [
                    'id' => $item->location_id,
                    'name' => $item->location_name,
                    'display_text' => $item->display_text,
                    'municipality_id' => $item->municipality_id,
                    'municipality_name' => $item->municipality_name,
                    'state_id' => $item->state_id,
                    'state_name' => $item->state_name,
                ];
            })->concat($additionalResults)->take(20)->values();
        } else {
            $results = $priorityResults->map(function ($item) {
                return [
                    'id' => $item->location_id,
                    'name' => $item->location_name,
                    'display_text' => $item->display_text,
                    'municipality_id' => $item->municipality_id,
                    'municipality_name' => $item->municipality_name,
                    'state_id' => $item->state_id,
                    'state_name' => $item->state_name,
                ];
            });
        }

        return response()->json($results);
    }

    /**
     * Expande abreviaciones comunes antes de la búsqueda
     */
    private function expandAbbreviations($text)
    {
        $text = strtolower(trim($text));
        
        // MAPEOS ESPECÍFICOS PRIMERO (casos completos)
        $specificMappings = [
            'jario pantoja' => 'jario y pantoja',
            'cd altamirano' => 'ciudad altamirano',
            'c altamirano' => 'ciudad altamirano',
            'ciu alta' => 'ciudad altamirano',
            'altamirano' => 'ciudad altamirano',
            'cetina' => 'centia',
            'changata gro' => 'changata',
            'ixtapilla gro' => 'ixtapilla',
            'coyuca de catalan' => 'coyuca de catalán',
            'el guayavo' => 'el guayabo',
            'los pozos gro' => 'los pozos',
        ];
        
        // Verificar mapeos específicos primero
        if (isset($specificMappings[$text])) {
            return $specificMappings[$text];
        }
        
        // ABREVIACIONES PALABRA POR PALABRA
        $abbreviations = [
            // Abreviaciones geográficas comunes
            'cd' => 'ciudad',
            'c' => 'ciudad', // Solo cuando está al inicio
            'nte' => 'norte',
            'norte' => 'norte',
            'ote' => 'oriente',
            'oriente' => 'oriente',
            'pte' => 'poniente',
            'poniente' => 'poniente',
            'sur' => 'sur',
            'cto' => 'centro',
            'centro' => 'centro',
            'col' => 'colonia',
            'colonia' => 'colonia',
            'frac' => 'fraccionamiento',
            'fracc' => 'fraccionamiento',
            'fraccionamiento' => 'fraccionamiento',
            'unidad' => 'unidad',
            'unid' => 'unidad',
            'barrio' => 'barrio',
            'bo' => 'barrio',
            'san' => 'san',
            'santa' => 'santa',
            'santo' => 'santo',
            'sta' => 'santa',
            'sto' => 'santo',
            'sn' => 'san',
            // Números comunes
            '1' => 'uno',
            '2' => 'dos',
            '3' => 'tres',
            '4' => 'cuatro',
            '5' => 'cinco',
            '1a' => 'primera',
            '2a' => 'segunda',
            '3a' => 'tercera'
        ];

        $words = explode(' ', $text);
        $expandedWords = [];

        foreach ($words as $index => $word) {
            // Manejar abreviación "c" solo al inicio como "ciudad"
            if ($word === 'c' && $index === 0) {
                $expandedWords[] = 'ciudad';
            } elseif (isset($abbreviations[$word])) {
                $expandedWords[] = $abbreviations[$word];
            } else {
                $expandedWords[] = $word;
            }
        }

        return implode(' ', $expandedWords);
    }

    /**
     * Limpia artículos, preposiciones y palabras comunes para mejorar la búsqueda
     */
    private function cleanCommonWords($text)
    {
        $common = [
            'el', 'la', 'los', 'las', 'de', 'del', 'y', 'en', 'a', 'san', 'santa', 'santo', 'do', 'da', 'das', 'dos', 'al', 'por', 'con', 'para', 'the', 'of', 'and'
        ];
        $words = explode(' ', $text);
        $filtered = array_filter($words, function($w) use ($common) {
            return !in_array($w, $common);
        });
        return implode(' ', $filtered);
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
        $priorityResults = PriorityLocation::where(function ($query) use ($searchTerm, $normalizedSearchTerm) {
            $query->where('normalized_name', 'like', "%$normalizedSearchTerm%")
                  ->orWhere('location_name', 'like', "%$searchTerm%")
                  ->orWhere('display_text', 'like', "%$searchTerm%")
                  ->orWhere('display_text', 'like', "%$normalizedSearchTerm%");
        })
        ->limit(20)
        ->get();
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
     * Detección automática de la mejor localidad
     * Devuelve solo UNA localidad con alta confianza, priorizando Guerrero y Michoacán
     */
    public function autoDetectLocation(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:2|max:100'
        ]);

        $searchTerm = trim($request->search);
        
        // 1. Expandir abreviaciones ANTES de normalizar
        $expandedSearchTerm = $this->expandAbbreviations($searchTerm);
        
        // 2. Normalizar y limpiar
        $normalizedSearchTerm = $this->normalizeText($expandedSearchTerm);
        $cleanedSearchTerm = $this->cleanCommonWords($normalizedSearchTerm);

        $bestMatch = null;
        $bestScore = PHP_INT_MAX;
        $confidenceLevel = 0;

        // También buscar con el término original (sin expandir) para comparar
        $originalNormalized = $this->normalizeText($searchTerm);
        $originalCleaned = $this->cleanCommonWords($originalNormalized);

        // 1. Buscar primero en estados SUPER PRIORITARIOS (Guerrero y Michoacán)
        $superPriorityResults = PriorityLocation::whereIn('state_id', [12, 16]) // Guerrero y Michoacán
            ->where(function ($query) use ($searchTerm, $expandedSearchTerm, $normalizedSearchTerm, $cleanedSearchTerm, $originalNormalized, $originalCleaned) {
                $query->whereRaw('LOWER(normalized_name) like ?', ["%" . strtolower($cleanedSearchTerm) . "%"])
                      ->orWhereRaw('LOWER(location_name) like ?', ["%" . strtolower($searchTerm) . "%"])
                      ->orWhereRaw('LOWER(location_name) like ?', ["%" . strtolower($expandedSearchTerm) . "%"])
                      ->orWhereRaw('LOWER(display_text) like ?', ["%" . strtolower($searchTerm) . "%"])
                      ->orWhereRaw('LOWER(display_text) like ?', ["%" . strtolower($expandedSearchTerm) . "%"])
                      ->orWhereRaw('LOWER(normalized_name) like ?', ["%" . strtolower($originalCleaned) . "%"]);
            })
            ->get();

        foreach ($superPriorityResults as $location) {
            $score = $this->calculateAdvancedScore($location->location_name, $searchTerm, $expandedSearchTerm, $normalizedSearchTerm, $cleanedSearchTerm, 1); // Prioridad máxima
            if ($score < $bestScore) {
                $bestScore = $score;
                $bestMatch = $location;
                $confidenceLevel = $this->calculateConfidence($score, 1);
            }
        }

        // 2. Si no hay coincidencia suficiente, buscar en otros estados prioritarios
        if ($confidenceLevel < 85) {
            $otherPriorityResults = PriorityLocation::whereNotIn('state_id', [12, 16])
                ->where(function ($query) use ($searchTerm, $normalizedSearchTerm, $cleanedSearchTerm) {
                    $query->whereRaw('LOWER(normalized_name) like ?', ["%" . strtolower($cleanedSearchTerm) . "%"])
                          ->orWhereRaw('LOWER(location_name) like ?', ["%" . strtolower($searchTerm) . "%"])
                          ->orWhereRaw('LOWER(display_text) like ?', ["%" . strtolower($searchTerm) . "%"]);
                })
                ->get();

            foreach ($otherPriorityResults as $location) {
                $score = $this->calculateAdvancedScore($location->location_name, $searchTerm, $expandedSearchTerm, $normalizedSearchTerm, $cleanedSearchTerm, $location->priority_level);
                if ($score < $bestScore) {
                    $bestScore = $score;
                    $bestMatch = $location;
                    $confidenceLevel = $this->calculateConfidence($score, $location->priority_level);
                }
            }
        }

        // 3. Si aún no hay coincidencia suficiente, buscar en TODAS las localidades
        if ($confidenceLevel < 80) {
            $allResults = Location::with(['municipality.state'])
                ->where(function ($query) use ($searchTerm, $normalizedSearchTerm, $cleanedSearchTerm) {
                    $query->whereRaw('LOWER(name) like ?', ["%" . strtolower($searchTerm) . "%"])
                          ->orWhereRaw('LOWER(name) like ?', ["%" . strtolower($cleanedSearchTerm) . "%"]);
                })
                ->limit(50) // Limitar para performance
                ->get();

            foreach ($allResults as $location) {
                $statePriority = $this->getStatePriority($location->municipality->state->id);
                $score = $this->calculateAdvancedScore($location->name, $searchTerm, $expandedSearchTerm, $normalizedSearchTerm, $cleanedSearchTerm, $statePriority);
                if ($score < $bestScore) {
                    $bestScore = $score;
                    $bestMatch = $location;
                    $confidenceLevel = $this->calculateConfidence($score, $statePriority);
                }
            }
        }

        // Solo devolver resultado si tenemos alta confianza (>= 75%)
        if ($bestMatch && $confidenceLevel >= 75) {
            if ($bestMatch instanceof \App\Models\PriorityLocation) {
                return response()->json([
                    'success' => true,
                    'location' => [
                        'id' => $bestMatch->location_id,
                        'name' => $bestMatch->location_name,
                        'display_text' => $bestMatch->display_text,
                        'municipality_id' => $bestMatch->municipality_id,
                        'municipality_name' => $bestMatch->municipality_name,
                        'state_id' => $bestMatch->state_id,
                        'state_name' => $bestMatch->state_name,
                    ],
                    'confidence' => $confidenceLevel,
                    'score' => $bestScore
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'location' => [
                        'id' => $bestMatch->id,
                        'name' => $bestMatch->name,
                        'display_text' => $bestMatch->name . ' - ' . $bestMatch->municipality->name . ', ' . $bestMatch->municipality->state->name,
                        'municipality_id' => $bestMatch->municipality->id,
                        'municipality_name' => $bestMatch->municipality->name,
                        'state_id' => $bestMatch->municipality->state->id,
                        'state_name' => $bestMatch->municipality->state->name,
                    ],
                    'confidence' => $confidenceLevel,
                    'score' => $bestScore
                ]);
            }
        }

        // No hay coincidencia suficiente
        return response()->json([
            'success' => false,
            'message' => 'No se encontró una localidad con suficiente confianza. Intenta ser más específico.',
            'confidence' => $confidenceLevel
        ]);
    }

    /**
     * Obtiene prioridad del estado
     */
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

    /**
     * Cálculo avanzado de score para detección automática con soporte para abreviaciones
     */
    private function calculateAdvancedScore($locationName, $originalSearchTerm, $expandedSearchTerm, $normalizedSearchTerm, $cleanedSearchTerm, $statePriority)
    {
        $locationNormalized = $this->normalizeText($locationName);
        $locationCleaned = $this->cleanCommonWords($locationNormalized);
        
        // Bonus por estado prioritario (Guerrero y Michoacán tienen bonus extra)
        $priorityBonus = 0;
        if ($statePriority === 1) { // Guerrero/Michoacán
            $priorityBonus = -3000;
        } elseif ($statePriority <= 3) { // Otros estados prioritarios
            $priorityBonus = -1500;
        }
        
        $score = $statePriority * 500 + $priorityBonus;

        // BONUS ESPECIAL: Penalizar localidades con números cuando hay alternativas más exactas
        if (preg_match('/\b(uno|dos|tres|cuatro|cinco|1|2|3|4|5)\b/i', $locationName)) {
            $score += 200; // Penalización por tener números
        }

        // 1. Coincidencias EXACTAS con término expandido (máxima prioridad)
        if (strtolower($locationName) === strtolower($expandedSearchTerm)) {
            return $score - 500; // Bonus extra para coincidencia exacta expandida
        }
        
        if ($locationNormalized === $normalizedSearchTerm) {
            return $score - 400;
        }
        
        if ($locationCleaned === $cleanedSearchTerm) {
            return $score - 300;
        }

        // 2. Coincidencias exactas con término original
        if (strtolower($locationName) === strtolower($originalSearchTerm)) {
            return $score + 1;
        }
        
        // 3. Empieza con el término expandido (muy bueno)
        if (stripos($locationName, $expandedSearchTerm) === 0) {
            return $score + 5;
        }
        
        if (strpos($locationNormalized, $normalizedSearchTerm) === 0) {
            return $score + 10;
        }
        
        if (strpos($locationCleaned, $cleanedSearchTerm) === 0) {
            return $score + 15;
        }

        // 4. Empieza con término original
        if (stripos($locationName, $originalSearchTerm) === 0) {
            return $score + 20;
        }
        
        // 5. Contiene el término expandido completo
        if (stripos($locationName, $expandedSearchTerm) !== false) {
            return $score + 25;
        }
        
        if (strpos($locationNormalized, $normalizedSearchTerm) !== false) {
            return $score + 30;
        }
        
        if (strpos($locationCleaned, $cleanedSearchTerm) !== false) {
            return $score + 35;
        }

        // 6. Contiene término original
        if (stripos($locationName, $originalSearchTerm) !== false) {
            return $score + 40;
        }
        
        // 7. Similitud fuzzy con término expandido (errores de escritura)
        $distance = levenshtein($cleanedSearchTerm, $locationCleaned);
        if ($distance <= 2) {
            return $score + 45 + ($distance * 5);
        }
        
        // 8. Coincidencia por iniciales del término expandido
        $searchInitials = implode('', array_map(fn($w) => $w[0] ?? '', explode(' ', $cleanedSearchTerm)));
        $locationInitials = implode('', array_map(fn($w) => $w[0] ?? '', explode(' ', $locationCleaned)));
        if ($searchInitials === $locationInitials && strlen($searchInitials) > 1) {
            return $score + 60;
        }
        
        // 9. Alias manuales específicos para casos problemáticos
        $alias = [
            'jario pantoja' => 'jario y pantoja',
            'el guayavo' => 'el guayabo',
            'cetina' => 'centia',
            'altamirano' => 'ciudad altamirano', // Nuevo alias específico
            'cd altamirano' => 'ciudad altamirano',
            'c altamirano' => 'ciudad altamirano'
        ];
        
        $searchKey = strtolower($originalSearchTerm);
        if (isset($alias[$searchKey])) {
            $aliasNormalized = $this->normalizeText($alias[$searchKey]);
            if ($locationNormalized === $aliasNormalized) {
                return $score - 100; // Bonus extra para alias específicos
            }
        }
        
        // Score muy alto para resultados irrelevantes
        return $score + 1000;
    }

    /**
     * Calcula el nivel de confianza basado en el score
     */
    private function calculateConfidence($score, $statePriority)
    {
        // Ajustar score base según prioridad
        $baseScore = $statePriority * 500;
        if ($statePriority === 1) {
            $baseScore -= 2000; // Guerrero/Michoacán
        } elseif ($statePriority <= 3) {
            $baseScore -= 1000; // Otros prioritarios
        }
        
        $adjustedScore = $score - $baseScore;
        
        // Calcular confianza inversa (menor score = mayor confianza)
        if ($adjustedScore <= 5) {
            return 100; // Coincidencia exacta o casi exacta
        } elseif ($adjustedScore <= 25) {
            return 95; // Muy buena coincidencia
        } elseif ($adjustedScore <= 45) {
            return 85; // Buena coincidencia
        } elseif ($adjustedScore <= 70) {
            return 75; // Coincidencia aceptable
        } elseif ($adjustedScore <= 100) {
            return 65; // Coincidencia baja
        } else {
            return 50; // Muy baja confianza
        }
    }

    /**
     * Obtiene la prioridad de un estado
     */
    private function getStatePriority($stateId)
    {
        $priorities = [
            12 => 1, // Guerrero
            16 => 1, // Michoacán (mismo nivel que Guerrero)
            15 => 3, // México
            17 => 4, // Morelos
            21 => 5, // Puebla
            20 => 5, // Oaxaca
        ];
        
        return $priorities[$stateId] ?? 10; // Estados no prioritarios
    }

    /**
     * Mapeo inteligente para localidades con entrada de texto plano
     * Maneja las variaciones comunes encontradas en datos legacy
     */
    public function findOrCreateLocationFromText(Request $request)
    {
        $request->validate([
            'location_text' => 'required|string|max:255',
            'municipality_text' => 'nullable|string|max:255',
            'state_text' => 'nullable|string|max:255',
        ]);

        $locationText = trim($request->location_text);
        $municipalityText = trim($request->municipality_text ?? '');
        $stateText = trim($request->state_text ?? '');

        // Mapeo manual para casos específicos encontrados en hospital.sql
        $knownMappings = [
            // Casos específicos de localidades problemáticas
            'jario pantoja' => 'jario y pantoja',
            'cd altamirano' => 'ciudad altamirano',
            'cd altamirano gro' => 'ciudad altamirano',
            'c altamirano' => 'ciudad altamirano',
            'cetina' => 'centia',
            'changata gro' => 'changata',
            'ixtapilla gro' => 'ixtapilla',
            'coyuca de catalan' => 'coyuca de catalán',
            'tlapehuala' => 'tlapehuala',
            
            // Casos con estado incluido en el nombre
            'los pozos gro' => 'los pozos',
            
            // Abreviaciones comunes
            'sn' => 'san',
            'sta' => 'santa',
            'sto' => 'santo',
        ];

        // Normalizar texto de entrada
        $normalizedLocation = $this->normalizeLocationText($locationText);
        
        // Verificar mapeo manual primero
        if (isset($knownMappings[strtolower($normalizedLocation)])) {
            $normalizedLocation = $knownMappings[strtolower($normalizedLocation)];
        }

        // Intentar encontrar la localidad en el sistema
        $searchResult = $this->searchLocationIntelligently($normalizedLocation, $municipalityText, $stateText);

        if ($searchResult) {
            return response()->json([
                'success' => true,
                'location' => $searchResult,
                'action' => 'found',
                'original_text' => $locationText,
                'normalized_text' => $normalizedLocation
            ]);
        }

        // Si no se encuentra, crear una nueva entrada temporal o devolver sugerencias
        return response()->json([
            'success' => false,
            'message' => 'Localidad no encontrada en el sistema',
            'suggestions' => $this->generateLocationSuggestions($normalizedLocation),
            'original_text' => $locationText,
            'normalized_text' => $normalizedLocation
        ]);
    }

    /**
     * Normaliza texto de localidad eliminando sufijos de estado y limpiando formato
     */
    private function normalizeLocationText($text)
    {
        $text = trim(strtolower($text));
        
        // Remover sufijos de estado comunes
        $stateSuffixes = [' gro', ' guerrero', ' mich', ' michoacan', ' mex', ' mexico'];
        foreach ($stateSuffixes as $suffix) {
            if (str_ends_with($text, $suffix)) {
                $text = trim(substr($text, 0, -strlen($suffix)));
            }
        }
        
        // Expandir abreviaciones
        $text = $this->expandAbbreviations($text);
        
        return $text;
    }

    /**
     * Búsqueda inteligente considerando variaciones y contexto
     */
    private function searchLocationIntelligently($locationText, $municipalityText = '', $stateText = '')
    {
        // Priorizar estados de Guerrero y Michoacán
        $priorityStateIds = [12, 16]; // Guerrero, Michoacán
        
        // Búsqueda exacta primero
        $exactMatch = PriorityLocation::where('location_name', 'ILIKE', $locationText)
            ->when(!empty($municipalityText), function ($query) use ($municipalityText) {
                return $query->where('municipality_name', 'ILIKE', "%{$municipalityText}%");
            })
            ->when(!empty($stateText), function ($query) use ($stateText) {
                return $query->where('state_name', 'ILIKE', "%{$stateText}%");
            })
            ->orderByRaw('CASE WHEN state_id IN (' . implode(',', $priorityStateIds) . ') THEN 0 ELSE 1 END')
            ->first();

        if ($exactMatch) {
            return $this->formatLocationResponse($exactMatch);
        }

        // Búsqueda fuzzy con normalización
        $normalizedSearch = $this->normalizeText($locationText);
        $cleanedSearch = $this->cleanCommonWords($normalizedSearch);

        $fuzzyMatch = PriorityLocation::whereRaw('LOWER(normalized_name) LIKE ?', ["%{$cleanedSearch}%"])
            ->when(!empty($municipalityText), function ($query) use ($municipalityText) {
                return $query->where('municipality_name', 'ILIKE', "%{$municipalityText}%");
            })
            ->orderByRaw('CASE WHEN state_id IN (' . implode(',', $priorityStateIds) . ') THEN 0 ELSE 1 END')
            ->orderBy('location_name')
            ->first();

        if ($fuzzyMatch) {
            return $this->formatLocationResponse($fuzzyMatch);
        }

        return null;
    }

    /**
     * Genera sugerencias para localidades no encontradas
     */
    private function generateLocationSuggestions($locationText)
    {
        $suggestions = PriorityLocation::whereRaw('LOWER(location_name) LIKE ?', ["%{$locationText}%"])
            ->orWhereRaw('LOWER(normalized_name) LIKE ?', ["%{$locationText}%"])
            ->limit(5)
            ->get()
            ->map(function ($location) use ($locationText) {
                $similarity = similar_text(strtolower($location->location_name), strtolower($locationText));
                return [
                    'id' => $location->location_id,
                    'name' => $location->location_name,
                    'display_text' => $location->display_text,
                    'municipality_id' => $location->municipality_id,
                    'municipality_name' => $location->municipality_name,
                    'state_id' => $location->state_id,
                    'state_name' => $location->state_name,
                    'similarity' => $similarity
                ];
            })
            ->sortByDesc('similarity')
            ->values();

        return $suggestions;
    }

    /**
     * Formatea la respuesta de localidad encontrada
     */
    private function formatLocationResponse($location)
    {
        return [
            'id' => $location->location_id,
            'name' => $location->location_name,
            'display_text' => $location->display_text,
            'municipality_id' => $location->municipality_id,
            'municipality_name' => $location->municipality_name,
            'state_id' => $location->state_id,
            'state_name' => $location->state_name,
        ];
    }
}
