<?php

namespace App\Http\Controllers\Admin\Archive;

use App\Models\State;
use App\Models\Gender;
use App\Models\Archive;
use App\Models\Municipality;
use App\Models\Location;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArchiveController extends Controller
{
    public function index(Request $request)
    {
        $query = Archive::with(['gender', 'location.municipality.state'])
            ->orderBy('archive_number', 'asc');

        // Filtro por número de expediente (búsqueda exacta o parcial)
        if ($request->filled('archive_number')) {
            $archiveNumber = trim($request->archive_number);
            // Si es numérico, buscar exacto primero, luego parcial
            if (is_numeric($archiveNumber)) {
                $query->where(function($q) use ($archiveNumber) {
                    $q->where('archive_number', $archiveNumber)
                      ->orWhere('archive_number', 'like', '%' . $archiveNumber . '%');
                });
            } else {
                $query->where('archive_number', 'like', '%' . $archiveNumber . '%');
            }
        }

        // Filtro por nombre (búsqueda mejorada en nombre completo)
        if ($request->filled('name')) {
            $name = trim($request->name);
            if ($name !== '') {
                // Normalizar el texto de búsqueda (sin acentos y en minúsculas)
                $normalizedName = $this->removeAccents(strtolower($name));
                $nameWords = explode(' ', $normalizedName);
                
                $query->where(function ($q) use ($nameWords) {
                    foreach ($nameWords as $word) {
                        if (trim($word) !== '') {
                            $q->where(function ($subQ) use ($word) {
                                // Búsqueda sin acentos y case-insensitive en todos los campos de nombre
                                $subQ->whereRaw($this->buildNormalizedSearchQuery('name'), ["%$word%"])
                                     ->orWhereRaw($this->buildNormalizedSearchQuery('last_name_father'), ["%$word%"])
                                     ->orWhereRaw($this->buildNormalizedSearchQuery('last_name_mother'), ["%$word%"])
                                     // Búsqueda en nombre completo concatenado (PostgreSQL)
                                     ->orWhereRaw("LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(CONCAT(COALESCE(name, ''), ' ', COALESCE(last_name_father, ''), ' ', COALESCE(last_name_mother, '')), 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n'), 'à', 'a'), 'è', 'e'), 'ì', 'i'), 'ò', 'o'), 'ù', 'u'), 'ç', 'c')) LIKE ?", ["%$word%"]);
                            });
                        }
                    }
                });
            }
        }        // Filtro por género
        if ($request->filled('gender_id')) {
            $query->where('gender_id', $request->gender_id);
        }

        // Filtros de ubicación optimizados
        if ($request->filled('state_id')) {
            $query->whereHas('location.municipality.state', function ($q) use ($request) {
                $q->where('id', $request->state_id);
            });
        }

        if ($request->filled('municipality_id')) {
            $query->whereHas('location.municipality', function ($q) use ($request) {
                $q->where('id', $request->municipality_id);
            });
        }

        if ($request->filled('location_id')) {
            $query->whereHas('location', function ($q) use ($request) {
                $q->where('id', $request->location_id);
            });
        }


        // =============================
        // Filtros de fecha avanzados
        // =============================
        if ($request->filled('date_filter_type')) {
            $type = $request->input('date_filter_type');
            switch ($type) {
                case 'year':
                    if ($request->filled('filter_year')) {
                        $query->whereYear('admission_date', $request->input('filter_year'));
                    }
                    break;
                case 'month':
                    if ($request->filled('filter_year') && $request->filled('filter_month')) {
                        $query->whereYear('admission_date', $request->input('filter_year'));
                        $query->whereMonth('admission_date', $request->input('filter_month'));
                    } elseif ($request->filled('filter_month')) {
                        // Filtrar por mes en cualquier año
                        $query->whereMonth('admission_date', $request->input('filter_month'));
                    }
                    break;
                case 'day':
                    if ($request->filled('filter_year') && $request->filled('filter_month') && $request->filled('filter_day')) {
                        $query->whereYear('admission_date', $request->input('filter_year'));
                        $query->whereMonth('admission_date', $request->input('filter_month'));
                        $query->whereDay('admission_date', $request->input('filter_day'));
                    }
                    break;
                case 'range':
                    if ($request->filled('date_from')) {
                        $query->whereDate('admission_date', '>=', $request->input('date_from'));
                    }
                    if ($request->filled('date_to')) {
                        $query->whereDate('admission_date', '<=', $request->input('date_to'));
                    }
                    break;
            }
        }

        // Si viene all=true, retornar todos los resultados sin paginar
        if ($request->boolean('all')) {
            return response()->json([
                'data' => $query->get()
            ]);
        }

        // Paginación optimizada
        if ($request->has('skip') && $request->has('limit')) {
            $skip = max(0, (int) $request->input('skip'));
            $limit = min(100, max(1, (int) $request->input('limit'))); // Límite máximo de 100

            $total = $query->count();
            $archives = $query->skip($skip)->take($limit)->get();

            return response()->json([
                'data' => $archives,
                'total' => $total,
                'current_page' => floor($skip / $limit) + 1,
                'per_page' => $limit
            ]);
        }

        // Fallback con paginación por defecto
        return response()->json($query->paginate(50));
    }


    public function store(Request $request)
    {
        // Validar datos de entrada con mensajes personalizados
        $request->validate([
            'archive_number' => 'required|integer|unique:archive,archive_number',
            'last_name_father' => 'nullable|string|max:100',
            'last_name_mother' => 'nullable|string|max:100',
            'name' => 'nullable|string|max:100',
            'age' => 'nullable|integer',
            'gender_id' => 'nullable|integer|exists:genders,id',
            'classification' => 'nullable|string|max:100',
            'contact_last_name_father' => 'nullable|string|max:100',
            'contact_last_name_mother' => 'nullable|string|max:100',
            'contact_name' => 'nullable|string|max:100',
            'admission_date' => 'nullable|date',
            'address' => 'nullable|string|max:150',
            'location_id' => 'nullable|integer|exists:locations,id',
            'location_name' => 'nullable|string|max:100', // Para texto libre
            'trial304' => 'nullable|string|max:1'
        ], [
            'archive_number.unique' => 'El número de expediente ' . $request->archive_number . ' ya existe. Por favor, use un número diferente.',
            'archive_number.required' => 'El número de expediente es obligatorio.',
            'archive_number.integer' => 'El número de expediente debe ser un número entero.'
        ]);

        $data = $request->all();

        // LÓGICA MEJORADA: Mapeo inteligente de localidades en texto plano
        if (!$request->location_id && $request->location_name) {
            $locationName = trim($request->location_name);
            Log::info('🔍 Procesando localidad en texto plano: ' . $locationName);
            try {
                $foundLocation = $this->findOrCreateLocationIntelligently($locationName);
                if ($foundLocation && isset($foundLocation['id'])) {
                    $data['location_id'] = $foundLocation['id'];
                    $data['location_text'] = null;
                    Log::info('✅ Localidad mapeada (ID asignado)', [
                        'input' => $locationName,
                        'found_id' => $foundLocation['id'],
                        'found_name' => $foundLocation['name'] ?? null,
                        'municipality' => $foundLocation['municipality'] ?? null,
                        'state' => $foundLocation['state'] ?? null,
                        'confidence' => $foundLocation['confidence'] ?? 'unknown'
                    ]);
                } else {
                    // Intento secundario: si detectó municipio/estado intentar resolver una localidad concreta
                    if ($foundLocation && (isset($foundLocation['municipality']) || isset($foundLocation['state']))) {
                        $secondary = $this->resolveLocationFromMunicipality(
                            $locationName,
                            $foundLocation['municipality'] ?? null,
                            $foundLocation['state'] ?? null
                        );
                        if ($secondary) {
                            $data['location_id'] = $secondary['id'];
                            $data['location_text'] = null;
                            Log::info('🔁 Resuelto en intento secundario', [
                                'input' => $locationName,
                                'resolved_id' => $secondary['id'],
                                'resolved_name' => $secondary['name'],
                                'municipality' => $secondary['municipality'],
                                'state' => $secondary['state'],
                                'similarity' => $secondary['similarity'] ?? null
                            ]);
                        }
                    }
                    if (empty($data['location_id'])) {
                    $data['location_id'] = null;
                    $data['location_text'] = $locationName; // Guardar texto plano sin adornos
                    Log::info('ℹ️ Guardando localidad como texto plano (sin match confiable)', [ 'input' => $locationName ]);
                    }
                }
            } catch (\Throwable $e) {
                $data['location_id'] = null;
                $data['location_text'] = $locationName;
                Log::error('❌ Error en mapeo, fallback a texto plano', [ 'input' => $locationName, 'exception' => $e->getMessage() ]);
            }
        }

        // Remover location_name del array antes de crear el registro
        unset($data['location_name']);

        $archive = Archive::create($data);

        return response()->json([
            'message' => 'Registro creado correctamente.',
            'archive' => $archive
        ], 201);
    }

    public function show($id)
    {
        $archive = Archive::with(['gender', 'location.municipality.state'])->find($id);

        if (!$archive) {
            return response()->json(['message' => 'Registro no encontrado.'], 404);
        }

        return response()->json(['archive' => $archive]);
    }

    public function update(Request $request, $id)
    {
        $archive = Archive::find($id);

        if (!$archive) {
            return response()->json(['message' => 'Registro no encontrado.'], 404);
        }

        $request->validate([
            'last_name_father' => 'nullable|string|max:100',
            'last_name_mother' => 'nullable|string|max:100',
            'name' => 'nullable|string|max:100',
            'age' => 'nullable|integer',
            'gender_id' => 'nullable|integer|exists:genders,id',
            'classification' => 'nullable|string|max:100',
            'contact_last_name_father' => 'nullable|string|max:100',
            'contact_last_name_mother' => 'nullable|string|max:100',
            'contact_name' => 'nullable|string|max:100',
            'admission_date' => 'nullable|date',
            'address' => 'nullable|string|max:150',
            'location_id' => 'nullable|integer|exists:locations,id',
            'trial304' => 'nullable|string|max:1'
        ]);

        $archive->update($request->all());

        return response()->json([
            'message' => 'Registro actualizado correctamente.',
            'archive' => $archive
        ]);
    }

    public function destroy($id)
    {
        $archive = Archive::find($id);

        if (!$archive) {
            return response()->json(['message' => 'Registro no encontrado.'], 404);
        }

        $archive->delete();

        return response()->json([
            'message' => 'Registro eliminado correctamente.'
        ]);
    }

    public function genders()
    {
        return response()->json(Gender::select('id', 'name')->get());
    }

    /**
     * Estadísticas agregadas para el dashboard de Archivo
     */
    public function stats()
    {
        // =============================
        // Fuente de fecha unificada: usar admission_date si existe, si no created_at
        // Esto evita que un "fresh" o seed masivo marque todo como ingresado HOY.
        // =============================
    // Expresión reutilizable para fecha base
    $dateExprStr = 'DATE(COALESCE(admission_date, created_at))';

    // Rango de días para la serie (últimos 7 días)
    $daysBack = 6; // 7 días incluyendo hoy
        $today = now()->startOfDay();
        $seriesStart = now()->subDays($daysBack)->startOfDay();
        $weekAgo = now()->subDays(6)->startOfDay(); // Últimos 7 días
        $monthStart = now()->startOfMonth();

        // Total histórico (incluye todo)
        $totalArchives = Archive::count();

        // Obtener conteos agrupados para rango de la serie (optimizado en una sola query)
        $dailyRows = DB::table('archive')
            ->select(DB::raw($dateExprStr . ' as d'), DB::raw('COUNT(*) as total'))
            ->whereRaw($dateExprStr . ' BETWEEN ? AND ?', [$seriesStart->toDateString(), $today->toDateString()])
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->keyBy('d');

        // Construir serie continua (rellenar días sin datos)
        $dailySeries = [];
        for ($i = 0; $i <= $daysBack; $i++) {
            $date = $seriesStart->copy()->addDays($i)->toDateString();
            $dailySeries[] = [
                'date' => $date,
                'count' => (int) ($dailyRows[$date]->total ?? 0)
            ];
        }

        // Métricas principales (excluyen seed masivo porque se basan en admission_date cuando existe)
        $todayAdded = collect($dailySeries)->firstWhere('date', $today->toDateString())['count'] ?? 0;

        // Para semana y mes, usar el mismo campo (admission_date|created_at)
        $weekAdded = DB::table('archive')
            ->whereRaw($dateExprStr . ' BETWEEN ? AND ?', [$weekAgo->toDateString(), $today->toDateString()])
            ->count();
        $monthAdded = DB::table('archive')
            ->whereRaw($dateExprStr . ' BETWEEN ? AND ?', [$monthStart->toDateString(), $today->toDateString()])
            ->count();

        // Distribución por género de HOY basada en admission_date|created_at
        $gendersToday = Archive::select('gender_id', DB::raw('count(*) as total'))
            ->whereRaw($dateExprStr . ' = ?', [$today->toDateString()])
            ->groupBy('gender_id')
            ->pluck('total', 'gender_id');

        $genderNames = Gender::whereIn('id', $gendersToday->keys())->pluck('name', 'id');
        $byGender = [ 'male' => 0, 'female' => 0, 'other' => 0 ];
        foreach ($gendersToday as $genderId => $count) {
            $name = strtolower($genderNames[$genderId] ?? '');
            if (str_contains($name, 'masc') || str_contains($name, 'hombre')) {
                $byGender['male'] += $count;
            } elseif (str_contains($name, 'fem') || str_contains($name, 'mujer')) {
                $byGender['female'] += $count;
            } else {
                $byGender['other'] += $count;
            }
        }

        // Top 5 localidades últimos 7 días (usando admission_date|created_at)
        $topLocations = Archive::select('location_id', DB::raw('count(*) as total'))
            ->whereNotNull('location_id')
            ->whereRaw($dateExprStr . ' BETWEEN ? AND ?', [$weekAgo->toDateString(), $today->toDateString()])
            ->groupBy('location_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $locationData = [];
        if ($topLocations->count()) {
            $locationModels = Location::with(['municipality.state'])
                ->whereIn('id', $topLocations->pluck('location_id'))
                ->get()->keyBy('id');
            foreach ($topLocations as $row) {
                $loc = $locationModels[$row->location_id] ?? null;
                $display = $loc ? $loc->name : 'Sin localidad';
                if ($loc && $loc->municipality && $loc->municipality->state) {
                    $display .= ' (' . $loc->municipality->name . ', ' . $loc->municipality->state->name . ')';
                }
                $locationData[] = [
                    'name' => $display,
                    'count' => (int) $row->total
                ];
            }
        }

        return response()->json([
            'stats' => [
                'todayAdded' => $todayAdded,
                'weekAdded' => $weekAdded,
                'monthAdded' => $monthAdded,
                'totalArchives' => $totalArchives,
            ],
            'dailySeries' => $dailySeries, // Serie diaria para gráfica
            'byGender' => $byGender,
            'topLocations' => $locationData,
            'generated_at' => now()->toDateTimeString(),
            'date_basis' => 'COALESCE(admission_date, created_at)'
        ]);
    }

    public function states()
    {
        return response()->json(State::select('id', 'name')->get());
    }

    public function municipalities(Request $request)
    {
        $query = Municipality::query();

        if ($request->filled('state_id')) {
            $query->where('state_id', $request->state_id);
        }

        return response()->json($query->select('id', 'name', 'state_id')->get());
    }

    public function locations(Request $request)
    {
        $query = Location::query();

        if ($request->filled('municipality_id')) {
            $query->where('municipality_id', $request->municipality_id);
        }

        return response()->json($query->select('id', 'name', 'municipality_id')->get());
    }

    public function config()
    {
        return response()->json([
            'message' => 'Este endpoint está obsoleto. Usa /genders, /states, /municipalities y /locations por separado.'
        ], 410);
    }

    public function uploadBackup(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
            'type' => 'required|string'
        ]);

        $file = $request->file('file');
        $type = $request->input('type');
        $filename = $file->getClientOriginalName(); // Usa el nombre enviado por el frontend

        $path = $file->storeAs('backups', $filename, 'public');

        return response()->json(['message' => 'Respaldo guardado', 'filename' => $filename]);
    }

    public function listBackups()
    {
        $files = Storage::disk('public')->files('backups');
        $data = [];
        foreach ($files as $file) {
            $data[] = [
                'filename' => basename($file),
                'type' => pathinfo($file, PATHINFO_EXTENSION)
            ];
        }
        return response()->json(['data' => $data]);
    }

    public function downloadBackup($filename)
    {
        $path = 'backups/' . $filename;
        if (!Storage::disk('public')->exists($path)) {
            return response()->json(['message' => 'Archivo no encontrado'], 404);
        }
        $fullPath = Storage::disk('public')->path($path);
        return response()->download($fullPath);
    }

    /**
     * Endpoint de prueba para verificar mapeo de localidades mejorado
     */
    public function testLocationMapping(Request $request)
    {
        $request->validate([
            'location_text' => 'required|string|max:255'
        ]);

        $locationText = trim($request->location_text);
        
        try {
            // Usar la función mejorada
            $mappedLocation = $this->findOrCreateLocationIntelligently($locationText);
            
            // Información adicional para debugging
            $debugInfo = [
                'original_text' => $locationText,
                'normalized_text' => $this->normalizeLocationText($locationText),
                'variations_tested' => $this->generateLocationVariations($this->normalizeLocationText($locationText)),
                'text_components' => $this->analyzeLocationText($locationText)
            ];
            
            return response()->json([
                'success' => true,
                'original_text' => $locationText,
                'mapped_location' => $mappedLocation,
                'debug_info' => $debugInfo,
                'message' => $mappedLocation ? 'Localidad encontrada/mapeada correctamente' : 'No se pudo mapear la localidad'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'original_text' => $locationText,
                'error' => $e->getMessage(),
                'message' => 'Error en el mapeo de localidad'
            ], 500);
        }
    }

    /**
     * Mapeo inteligente mejorado para localidades con entrada de texto plano
     * Prioriza Guerrero y Michoacán de Ocampo con algoritmos avanzados de detección
     */
    private function findOrCreateLocationIntelligently($locationText)
    {
        // Normalizar y limpiar texto de entrada
        $cleanText = $this->normalizeLocationText($locationText);
        $searchKey = strtolower($cleanText);

        // 1. BÚSQUEDA EXACTA EN LOCALIDADES PRIORITARIAS (Guerrero y Michoacán)
        $priorityMatch = $this->searchInPriorityLocations($searchKey);
        if ($priorityMatch && $priorityMatch['confidence'] === 'exact') {
            return $priorityMatch;
        }

        // 2. ANÁLISIS INTELIGENTE DEL TEXTO (detectar componentes)
        $textComponents = $this->analyzeLocationText($locationText);
        
        // 3. BÚSQUEDA POR COMPONENTES DETECTADOS
        if ($textComponents['detected_state'] || $textComponents['detected_municipality']) {
            $componentMatch = $this->searchByDetectedComponents($textComponents, $searchKey);
            if ($componentMatch) {
                return $componentMatch;
            }
        }

        // 4. BÚSQUEDA FUZZY EN LOCALIDADES PRIORITARIAS
        if ($priorityMatch && $priorityMatch['confidence'] === 'high') {
            return $priorityMatch;
        }

        // 5. BÚSQUEDA EN MUNICIPIOS DE GUERRERO Y MICHOACÁN
        $municipalityMatch = $this->searchInPriorityMunicipalities($searchKey, $textComponents);
        if ($municipalityMatch) {
            return $municipalityMatch;
        }

        // 6. BÚSQUEDA GENERAL EN TODO EL PAÍS (como respaldo)
        $generalMatch = $this->searchInAllLocations($searchKey);
        if ($generalMatch) {
            return $generalMatch;
        }

        // 7. BÚSQUEDA FUZZY GENERAL
        $fuzzyMatch = $this->performFuzzySearch($searchKey, $textComponents);
        if ($fuzzyMatch) {
            return $fuzzyMatch;
        }

        // 7.5 BÚSQUEDA PARCIAL AMPLIA (palabras clave dentro de priority_locations)
        $broad = $this->broadPartialLocationSearch($searchKey);
        if ($broad) {
            return $broad;
        }

        // 8. Si solo detectó estado, devolverlo
        if ($textComponents['detected_state']) {
            return [
                'municipality' => null,
                'state' => $textComponents['detected_state'],
                'suggestion' => true,
                'confidence' => 'state_only'
            ];
        }

        // 9. No se encontró coincidencia
        return null;
    }

    /**
     * Búsqueda en localidades prioritarias usando la tabla optimizada
     */
    private function searchInPriorityLocations($searchKey)
    {
        // 1. Buscar coincidencia exacta primero
        $exactMatch = DB::table('priority_locations')
            ->where('normalized_name', $searchKey)
            ->orderBy('priority_level')
            ->first();

        if ($exactMatch) {
            return [
                'id' => $exactMatch->location_id,
                'name' => $exactMatch->location_name,
                'municipality' => $exactMatch->municipality_name,
                'state' => $exactMatch->state_name,
                'suggestion' => false,
                'confidence' => 'exact',
                'priority_level' => $exactMatch->priority_level
            ];
        }

        // 2. Búsqueda con variaciones de conectores
        $variations = $this->generateLocationVariations($searchKey);
        foreach ($variations as $variation) {
            $variationMatch = DB::table('priority_locations')
                ->where('normalized_name', $variation)
                ->orderBy('priority_level')
                ->first();
                
            if ($variationMatch) {
                return [
                    'id' => $variationMatch->location_id,
                    'name' => $variationMatch->location_name,
                    'municipality' => $variationMatch->municipality_name,
                    'state' => $variationMatch->state_name,
                    'suggestion' => false,
                    'confidence' => 'exact_variation',
                    'priority_level' => $variationMatch->priority_level,
                    'matched_variation' => $variation
                ];
            }
        }

        // 3. Búsqueda fuzzy inteligente con palabras clave
        $fuzzyMatches = $this->performIntelligentFuzzySearch($searchKey);
        if (!empty($fuzzyMatches)) {
            return $fuzzyMatches[0]; // Retornar el mejor match
        }

        // 4. Búsqueda por palabras individuales (para casos muy fragmentados)
        $wordMatches = $this->searchByIndividualWords($searchKey);
        if (!empty($wordMatches)) {
            return $wordMatches[0]; // Retornar el mejor match
        }

        return null;
    }

    /**
     * Genera variaciones de una localidad con diferentes conectores
     */
    private function generateLocationVariations($locationName)
    {
        $variations = [];
        $words = explode(' ', trim($locationName));
        
        if (count($words) < 2) {
            return $variations; // No hay variaciones posibles
        }

        // Conectores comunes en nombres de localidades mexicanas
        $connectors = ['y', 'de', 'del', 'la', 'las', 'los', 'el', 'san', 'santa', 'santo'];
        
        // Generar variaciones agregando conectores entre palabras
        for ($i = 0; $i < count($words) - 1; $i++) {
            foreach ($connectors as $connector) {
                $newWords = $words;
                array_splice($newWords, $i + 1, 0, $connector);
                $variations[] = implode(' ', $newWords);
            }
        }
        
        // Variaciones quitando conectores existentes
        $filteredWords = array_filter($words, function($word) use ($connectors) {
            return !in_array(strtolower($word), $connectors);
        });
        
        if (count($filteredWords) !== count($words)) {
            $variations[] = implode(' ', $filteredWords);
        }
        
        // Variaciones con conectores específicos comunes
        if (count($words) == 2) {
            $variations[] = $words[0] . ' y ' . $words[1];
            $variations[] = $words[0] . ' de ' . $words[1];
            $variations[] = $words[0] . ' del ' . $words[1];
            $variations[] = $words[0] . ' la ' . $words[1];
        }
        
        return array_unique($variations);
    }

    /**
     * Realiza búsqueda fuzzy inteligente en localidades prioritarias
     */
    private function performIntelligentFuzzySearch($searchKey)
    {
        $words = explode(' ', $searchKey);
        $results = [];
        
        // Buscar localidades que contengan todas las palabras principales
        $query = DB::table('priority_locations');
        
        foreach ($words as $word) {
            if (strlen($word) >= 3) { // Solo palabras significativas
                $query->where('normalized_name', 'LIKE', '%' . $word . '%');
            }
        }
        
        $matches = $query->orderBy('priority_level')->limit(10)->get();

        // Fallback especial para errores comunes b/v ("guayavo" vs "guayabo") si no hubo matches por LIKE directo
        if ($matches->count() === 0 && str_contains($searchKey, 'v')) {
            $bvVariant = str_replace('v', 'b', $searchKey);
            $altQuery = DB::table('priority_locations');
            foreach ($words as $word) {
                if (strlen($word) >= 3) {
                    $altQuery->where(function($q) use ($word) {
                        // probar la variante b/v dentro del LIKE
                        $q->where('normalized_name', 'LIKE', '%' . $word . '%')
                          ->orWhere('normalized_name', 'LIKE', '%' . str_replace('v','b',$word) . '%');
                    });
                }
            }
            $matches = $altQuery->orderBy('priority_level')->limit(10)->get();
        }
        
        foreach ($matches as $match) {
            $similarity = $this->calculateAdvancedSimilarity($searchKey, $match->normalized_name);

            // Ajustar umbrales: >0.8 alta, 0.65-0.8 media, <0.65 ignorar
            if ($similarity >= 0.65) {
                $results[] = [
                    'id' => $match->location_id,
                    'name' => $match->location_name,
                    'municipality' => $match->municipality_name,
                    'state' => $match->state_name,
                    'suggestion' => $similarity < 0.8,
                    'confidence' => $similarity >= 0.8 ? 'high' : 'medium',
                    'similarity' => $similarity,
                    'priority_level' => $match->priority_level
                ];
            }
        }
        
        // Ordenar por similitud y prioridad
        usort($results, function($a, $b) {
            if ($a['priority_level'] !== $b['priority_level']) {
                return $a['priority_level'] <=> $b['priority_level'];
            }
            return $b['similarity'] <=> $a['similarity'];
        });
        
        return $results;
    }

    /**
     * Búsqueda por palabras individuales para casos muy fragmentados
     */
    private function searchByIndividualWords($searchKey)
    {
        $words = array_filter(explode(' ', $searchKey), function($word) {
            return strlen($word) >= 3; // Solo palabras significativas
        });
        
        if (empty($words)) {
            return [];
        }
        
        $results = [];
        
        // Buscar localidades que contengan al menos 2 palabras del input
        $locations = DB::table('priority_locations')
            ->orderBy('priority_level')
            ->get();
            
        foreach ($locations as $location) {
            $locationWords = explode(' ', $location->normalized_name);
            $matches = 0;
            $partialPenalty = 0; // penalizar coincidencias muy cortas

            foreach ($words as $searchWord) {
                foreach ($locationWords as $locationWord) {
                    if (strlen($locationWord) >= 3) {
                        // Igualdad directa
                        if ($locationWord === $searchWord) {
                            $matches++;
                            break;
                        }
                        // Coincidencia por prefijo pero exigir al menos 70% del largo
                        if (strpos($searchWord, $locationWord) === 0 || strpos($locationWord, $searchWord) === 0) {
                            $ratio = min(strlen($searchWord), strlen($locationWord)) / max(strlen($searchWord), strlen($locationWord));
                            if ($ratio >= 0.7) {
                                $matches++;
                                break;
                            } else {
                                $partialPenalty += 0.3; // demasiada diferencia (evita "guay" vs "guayavo")
                            }
                        }
                    }
                }
            }

            $effectiveMatches = max(0, $matches - $partialPenalty); // resta penalizaciones
            $matchPercentage = $effectiveMatches / count($words);

            if ($matchPercentage >= 0.6) { // elevar umbral mínimo
                $similarity = $this->calculateAdvancedSimilarity($searchKey, $location->normalized_name);

                // Filtrar similitudes muy bajas (<0.55) para evitar falsos positivos
                if ($similarity >= 0.55) {
                    $results[] = [
                        'id' => $location->location_id,
                        'name' => $location->location_name,
                        'municipality' => $location->municipality_name,
                        'state' => $location->state_name,
                        'suggestion' => true,
                        // confianza basada en similitud real
                        'confidence' => $similarity >= 0.8 ? 'high' : ($similarity >= 0.65 ? 'medium' : 'low'),
                        'similarity' => $similarity,
                        'match_percentage' => $matchPercentage,
                        'priority_level' => $location->priority_level
                    ];
                }
            }
        }
        
        // Ordenar por porcentaje de coincidencia y prioridad
        usort($results, function($a, $b) {
            if ($a['priority_level'] !== $b['priority_level']) {
                return $a['priority_level'] <=> $b['priority_level'];
            }
            if ($a['match_percentage'] !== $b['match_percentage']) {
                return $b['match_percentage'] <=> $a['match_percentage'];
            }
            return $b['similarity'] <=> $a['similarity'];
        });
        
        return array_slice($results, 0, 5); // Máximo 5 resultados
    }

    /**
     * Análisis inteligente del texto para detectar componentes (estado, municipio, localidad)
     */
    private function analyzeLocationText($locationText)
    {
        $text = strtolower(trim($locationText));
        $components = [
            'original_text' => $locationText,
            'normalized_text' => $text,
            'detected_state' => null,
            'detected_municipality' => null,
            'detected_location' => null,
            'has_separator' => false,
            'parts' => []
        ];

        // Detectar separadores comunes
        $separators = [',', '-', '/', '|', ';'];
        foreach ($separators as $sep) {
            if (strpos($text, $sep) !== false) {
                $components['has_separator'] = true;
                $components['parts'] = array_map('trim', explode($sep, $text));
                break;
            }
        }

        // Si no hay separadores, dividir por espacios largos o palabras clave
        if (!$components['has_separator']) {
            $parts = $this->splitByKeywords($text);
            if (count($parts) > 1) {
                $components['parts'] = $parts;
            } else {
                $components['parts'] = [$text];
            }
        }

        // Detectar estado en cada parte
        foreach ($components['parts'] as $part) {
            $detectedState = $this->detectStateFromText($part);
            if ($detectedState) {
                $components['detected_state'] = $detectedState;
                break;
            }
        }

        // Detectar patrones específicos de Guerrero y Michoacán
        $components = $this->detectSpecificPatterns($components);

        return $components;
    }

    /**
     * Divide texto por palabras clave geográficas
     */
    private function splitByKeywords($text)
    {
        $keywords = ['municipio', 'estado', 'gro', 'guerrero', 'mich', 'michoacan', 'michoacán'];
        
        foreach ($keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return array_map('trim', preg_split('/\b' . preg_quote($keyword) . '\b/', $text));
            }
        }
        
        return [$text];
    }

    /**
     * Detecta patrones específicos de Guerrero y Michoacán
     */
    private function detectSpecificPatterns($components)
    {
        $text = $components['normalized_text'];
        
        // Patrones mejorados para Guerrero
        if (preg_match('/\b(gro|guerrero)\b/', $text)) {
            $components['detected_state'] = 'Guerrero';
        }
        
        // Patrones mejorados para Michoacán
        if (preg_match('/\b(mich|michoacan|michoacán|michoacán de ocampo)\b/', $text)) {
            $components['detected_state'] = 'Michoacán de Ocampo';
        }
        
        // Detectar municipios conocidos de Guerrero
        $guerreroMunicipalities = [
            'acapulco', 'chilpancingo', 'iguala', 'taxco', 'zihuatanejo', 'petatlán',
            'teloloapan', 'arcelia', 'coyuca', 'atoyac', 'tecpan', 'san marcos'
        ];
        
        foreach ($guerreroMunicipalities as $muni) {
            if (strpos($text, $muni) !== false) {
                $components['detected_municipality'] = $muni;
                $components['detected_state'] = 'Guerrero';
                break;
            }
        }
        
        // Detectar municipios conocidos de Michoacán
        $michoacanMunicipalities = [
            'morelia', 'uruapan', 'zamora', 'lázaro cárdenas', 'apatzingán', 'zitácuaro',
            'sahuayo', 'pátzcuaro', 'la piedad', 'hidalgo', 'maravatío'
        ];
        
        foreach ($michoacanMunicipalities as $muni) {
            if (strpos($text, $muni) !== false) {
                $components['detected_municipality'] = $muni;
                $components['detected_state'] = 'Michoacán de Ocampo';
                break;
            }
        }
        
        return $components;
    }

    /**
     * Búsqueda por componentes detectados
     */
    private function searchByDetectedComponents($textComponents, $searchKey)
    {
        // Si se detectó estado, buscar en ese estado específicamente
        if ($textComponents['detected_state']) {
            $stateId = $textComponents['detected_state'] === 'Guerrero' ? 12 : 
                      ($textComponents['detected_state'] === 'Michoacán de Ocampo' ? 16 : null);
            
            if ($stateId) {
                // Buscar en priority_locations del estado detectado
                $stateMatch = DB::table('priority_locations')
                    ->where('state_id', $stateId)
                    ->where(function($query) use ($searchKey, $textComponents) {
                        $query->where('normalized_name', 'LIKE', '%' . $searchKey . '%');
                        
                        // Si se detectó municipio, priorizar esas búsquedas
                        if ($textComponents['detected_municipality']) {
                            $query->orWhere('municipality_name', 'LIKE', '%' . $textComponents['detected_municipality'] . '%');
                        }
                    })
                    ->orderBy('priority_level')
                    ->first();

                if ($stateMatch) {
                    $similarity = $this->calculateAdvancedSimilarity($searchKey, $stateMatch->normalized_name);
                    return [
                        'id' => $stateMatch->location_id,
                        'name' => $stateMatch->location_name,
                        'municipality' => $stateMatch->municipality_name,
                        'state' => $stateMatch->state_name,
                        'suggestion' => $similarity < 0.9,
                        'confidence' => $similarity >= 0.9 ? 'high' : 'medium',
                        'similarity' => $similarity
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Búsqueda en municipios prioritarios (Guerrero y Michoacán)
     */
    private function searchInPriorityMunicipalities($searchKey, $textComponents)
    {
        $targetStates = [12, 16]; // Guerrero y Michoacán
        
        // Si se detectó un estado específico, priorizarlo
        if ($textComponents['detected_state']) {
            $stateId = $textComponents['detected_state'] === 'Guerrero' ? 12 : 
                      ($textComponents['detected_state'] === 'Michoacán de Ocampo' ? 16 : null);
            if ($stateId) {
                $targetStates = [$stateId, ...array_diff([12, 16], [$stateId])];
            }
        }

        foreach ($targetStates as $stateId) {
            // Buscar municipio exacto
            $exactMuni = Municipality::with('state')
                ->where('state_id', $stateId)
                ->whereRaw("LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u')) = ?", [$searchKey])
                ->first();

            if ($exactMuni) {
                return [
                    'municipality' => $exactMuni->name,
                    'state' => $exactMuni->state->name,
                    'suggestion' => false,
                    'confidence' => 'exact'
                ];
            }

            // Buscar municipio fuzzy
            $fuzzyMuni = Municipality::with('state')
                ->where('state_id', $stateId)
                ->whereRaw("LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u')) LIKE ?", ['%' . $searchKey . '%'])
                ->first();

            if ($fuzzyMuni) {
                $similarity = $this->calculateAdvancedSimilarity($searchKey, strtolower($this->normalizeLocationText($fuzzyMuni->name)));
                if ($similarity >= 0.7) {
                    return [
                        'municipality' => $fuzzyMuni->name,
                        'state' => $fuzzyMuni->state->name,
                        'suggestion' => $similarity < 0.9,
                        'confidence' => $similarity >= 0.9 ? 'high' : 'medium',
                        'similarity' => $similarity
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Búsqueda en todas las localidades del país (respaldo)
     */
    private function searchInAllLocations($searchKey)
    {
        // Buscar coincidencia exacta
        $exactLocation = Location::with(['municipality.state'])
            ->whereRaw("LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u')) = ?", [$searchKey])
            ->first();

        if ($exactLocation) {
            return [
                'id' => $exactLocation->id,
                'name' => $exactLocation->name,
                'municipality' => $exactLocation->municipality->name,
                'state' => $exactLocation->municipality->state->name,
                'suggestion' => false,
                'confidence' => 'exact'
            ];
        }

        return null;
    }

    /**
     * Búsqueda fuzzy avanzada
     */
    private function performFuzzySearch($searchKey, $textComponents)
    {
        // Primero buscar en estados prioritarios
        $priorityStates = [12, 16]; // Guerrero y Michoacán
        
        foreach ($priorityStates as $stateId) {
            $fuzzyResults = Location::with(['municipality.state'])
                ->whereHas('municipality.state', function($q) use ($stateId) {
                    $q->where('id', $stateId);
                })
                ->whereRaw("LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u')) LIKE ?", ['%' . $searchKey . '%'])
                ->limit(3)
                ->get();

            if ($fuzzyResults->count() > 0) {
                $suggestions = [];
                foreach ($fuzzyResults as $location) {
                    $similarity = $this->calculateAdvancedSimilarity($searchKey, strtolower($this->normalizeLocationText($location->name)));
                    if ($similarity >= 0.6) {
                        $suggestions[] = [
                            'id' => $location->id,
                            'name' => $location->name,
                            'municipality' => $location->municipality->name,
                            'state' => $location->municipality->state->name,
                            'similarity' => $similarity
                        ];
                    }
                }
                
                if (!empty($suggestions)) {
                    // Ordenar por similitud
                    usort($suggestions, function($a, $b) {
                        return $b['similarity'] <=> $a['similarity'];
                    });
                    
                    return [
                        'suggestions' => $suggestions,
                        'suggestion' => true,
                        'confidence' => 'fuzzy'
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Calcula similitud avanzada entre dos textos con manejo inteligente de variaciones
     */
    private function calculateAdvancedSimilarity($text1, $text2)
    {
        // Normalizar ambos textos
        $norm1 = strtolower(trim($text1));
        $norm2 = strtolower(trim($text2));

    // Normalización adicional para tratar b/v como equivalentes (error común)
    $norm1bv = strtr($norm1, ['v' => 'b']);
    $norm2bv = strtr($norm2, ['v' => 'b']);
        
        // 1. Coincidencia exacta
        if ($norm1 === $norm2) {
            return 1.0;
        }
        
        // 2. Similitud por substring completo
        if (str_contains($norm2, $norm1) || str_contains($norm1, $norm2)) {
            return 0.95;
        }
        
        // 3. Preparar palabras para análisis
        $words1 = $this->extractSignificantWords($norm1);
        $words2 = $this->extractSignificantWords($norm2);
        
        // 4. Similitud por palabras principales (sin conectores)
        $commonWords = array_intersect($words1, $words2);
        $maxWords = max(count($words1), count($words2));
        $wordSimilarity = $maxWords > 0 ? count($commonWords) / $maxWords : 0;
        
        // 5. Bonificación por orden de palabras
        $orderBonus = $this->calculateWordOrderSimilarity($words1, $words2);
        
        // 6. Similitud por primera palabra con descriptores neutrales
        $descriptorWords = ['col','colonia','ciudad','cd','ejido','fracc','fraccionamiento','rancho','poblado','paraje','barrio','villa'];
        $firstWordScore = 0;
        if (!empty($words1) && !empty($words2)) {
            $firstWord1 = $words1[0];
            $firstWord2 = $words2[0];
            $isDesc1 = in_array($firstWord1, $descriptorWords);
            $isDesc2 = in_array($firstWord2, $descriptorWords);
            if ($firstWord1 === $firstWord2) {
                $firstWordScore = 0.4;
            } elseif (($isDesc1 && !$isDesc2) || ($isDesc2 && !$isDesc1)) {
                // neutral
            } elseif ($isDesc1 && $isDesc2) {
                $firstWordScore += 0.15;
            } elseif (strpos($firstWord1, $firstWord2) !== false || strpos($firstWord2, $firstWord1) !== false) {
                $firstWordScore = 0.3;
            } else {
                $lenMax = max(strlen($firstWord1), strlen($firstWord2));
                $levFW = $lenMax > 0 ? 1 - (levenshtein($firstWord1, $firstWord2)/$lenMax) : 0;
                if ($levFW < 0.4) { $firstWordScore -= 0.2; }
            }
        }
        
        // 7. Similitud por levenshtein normalizada
    $maxLen = max(strlen($norm1bv), strlen($norm2bv));
    $levenshtein = $maxLen > 0 ? 1 - (levenshtein($norm1bv, $norm2bv) / $maxLen) : 0;
        
        // 8. Similitud fonética (para casos como "jario" vs "jario y pantoja")
        $phoneticScore = $this->calculatePhoneticSimilarity($words1, $words2);
        
        // 9. Bonus por variaciones conocidas de conectores
        $variationBonus = $this->calculateVariationBonus($norm1, $norm2);
        
        // Combinar puntuaciones con pesos optimizados
        $finalScore = max(
            $wordSimilarity * 0.4 + $orderBonus * 0.2 + $firstWordScore,
            $levenshtein * 0.3 + $phoneticScore * 0.4 + $variationBonus * 0.3,
            $firstWordScore + $wordSimilarity * 0.5 + $variationBonus * 0.2
        );
        
        return min(1.0, $finalScore);
    }
    
    /**
     * Extrae palabras significativas eliminando conectores comunes
     */
    private function extractSignificantWords($text)
    {
    $commonWords = ['y', 'de', 'del', 'la', 'las', 'los', 'el', 'en', 'con', 'por', 'para', 'col', 'colonia', 'ciudad', 'cd', 'ejido', 'fracc', 'fraccionamiento', 'rancho'];
        $words = explode(' ', $text);
        
        return array_values(array_filter($words, function($word) use ($commonWords) {
            return strlen($word) >= 2 && !in_array($word, $commonWords);
        }));
    }
    
    /**
     * Calcula similitud por orden de palabras
     */
    private function calculateWordOrderSimilarity($words1, $words2)
    {
        if (empty($words1) || empty($words2)) {
            return 0;
        }
        
        $matches = 0;
        $maxIndex = min(count($words1), count($words2));
        
        for ($i = 0; $i < $maxIndex; $i++) {
            if (isset($words1[$i]) && isset($words2[$i]) && $words1[$i] === $words2[$i]) {
                $matches++;
            }
        }
        
        return $matches / $maxIndex;
    }
    
    /**
     * Calcula similitud fonética entre conjuntos de palabras
     */
    private function calculatePhoneticSimilarity($words1, $words2)
    {
        if (empty($words1) || empty($words2)) {
            return 0;
        }
        
        $phoneticMatches = 0;
        $totalComparisons = 0;
        
        foreach ($words1 as $word1) {
            foreach ($words2 as $word2) {
                $totalComparisons++;
                
                // Similitud por sonidos similares
                if ($this->soundsLike($word1, $word2)) {
                    $phoneticMatches++;
                }
            }
        }
        
        return $totalComparisons > 0 ? $phoneticMatches / $totalComparisons : 0;
    }
    
    /**
     * Determina si dos palabras suenan similar (algoritmo simplificado)
     */
    private function soundsLike($word1, $word2)
    {
        // Convertir a representación fonética simplificada
        $phonetic1 = $this->toPhonetic($word1);
        $phonetic2 = $this->toPhonetic($word2);
        
        // Calcular similitud fonética
        $maxLen = max(strlen($phonetic1), strlen($phonetic2));
        if ($maxLen === 0) return true;
        
        $distance = levenshtein($phonetic1, $phonetic2);
        return ($distance / $maxLen) <= 0.3; // 30% de diferencia permitida
    }
    
    /**
     * Convierte palabra a representación fonética simplificada
     */
    private function toPhonetic($word)
    {
        $word = strtolower($word);
        
        // Reemplazos fonéticos comunes en español
        $replacements = [
            'ph' => 'f',
            'ch' => 'x',
            'll' => 'y',
            'rr' => 'r',
            'qu' => 'k',
            'ce' => 'se',
            'ci' => 'si',
            'ge' => 'je',
            'gi' => 'ji',
            'gue' => 'ge',
            'gui' => 'gi',
            'ü' => 'u',
            'b' => 'v', // b y v suenan igual en español
            'z' => 's', // z y s suenan igual en muchas regiones
        ];
        
        foreach ($replacements as $from => $to) {
            $word = str_replace($from, $to, $word);
        }
        
        // Eliminar vocales repetidas
        $word = preg_replace('/([aeiou])\1+/', '$1', $word);
        
        return $word;
    }
    
    /**
     * Calcula bonus por variaciones conocidas de conectores
     */
    private function calculateVariationBonus($text1, $text2)
    {
        // Generar variaciones de ambos textos
        $variations1 = $this->generateLocationVariations($text1);
        $variations2 = $this->generateLocationVariations($text2);
        
        // Verificar si alguna variación coincide
        foreach ($variations1 as $var1) {
            if ($var1 === $text2) {
                return 0.8; // Alta bonificación por variación exacta
            }
            foreach ($variations2 as $var2) {
                if ($var1 === $var2) {
                    return 0.6; // Bonificación media por variaciones que coinciden
                }
            }
        }
        
        // Verificar variaciones con texto original
        foreach ($variations2 as $var2) {
            if ($var2 === $text1) {
                return 0.8;
            }
        }
        
        return 0;
    }

    /**
     * Detecta automáticamente el estado desde el texto de localidad - VERSIÓN MEJORADA
     */
    private function detectStateFromText($locationText)
    {
        $text = strtolower(trim($locationText));
        
        // Patrones mejorados para detectar Guerrero
        $guerreroPatterns = [
            '/\bgro\b/',
            '/\bguerrero\b/',
            '/, gro$/',
            '/, guerrero$/',
            '/ gro$/',
            '/ guerrero$/',
            '/guerrero de/',
            '/estado de guerrero/',
            '/gro\./',
            '/\(gro\)/',
            '/\[gro\]/',
        ];
        
        // Patrones mejorados para detectar Michoacán
        $michoacanPatterns = [
            '/\bmich\b/',
            '/\bmichoacan\b/',
            '/\bmichoacán\b/',
            '/michoacán de ocampo/',
            '/michoacan de ocampo/',
            '/, mich$/',
            '/, michoacan$/',
            '/, michoacán$/',
            '/ mich$/',
            '/ michoacan$/',
            '/ michoacán$/',
            '/estado de michoacán/',
            '/estado de michoacan/',
            '/mich\./',
            '/\(mich\)/',
            '/\[mich\]/',
        ];
        
        // Verificar patrones de Guerrero
        foreach ($guerreroPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return 'Guerrero';
            }
        }
        
        // Verificar patrones de Michoacán
        foreach ($michoacanPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return 'Michoacán de Ocampo';
            }
        }
        
        // Detectar por municipios conocidos de Guerrero
        $guerreroMunicipalities = [
            'acapulco', 'chilpancingo', 'iguala', 'taxco', 'zihuatanejo', 'petatlán',
            'teloloapan', 'arcelia', 'coyuca', 'atoyac', 'tecpan', 'san marcos',
            'altamirano', 'apaxtla', 'ayutla', 'azoyú', 'benito juárez', 'buenavista',
            'coahuayutla', 'cocula', 'copala', 'costa chica', 'cuajinicuilapa',
            'cualác', 'cutzamala', 'florencio villarreal', 'general canuto',
            'general heliodoro castillo', 'huamuxtitlán', 'huitzuco', 'iliatenco',
            'josé joaquín de herrera', 'juan r. escudero', 'juchitán', 'la unión',
            'leonardo bravo', 'malinaltepec', 'marquelia', 'mártir de cuilapan',
            'metlatónoc', 'mochitlán', 'olinalá', 'ometepec', 'pedro ascencio alquisiras',
            'pilcaya', 'pungarabato', 'quechultenango', 'san luis acatlán',
            'san miguel totolapan', 'tlacoachistlahuaca', 'tlalchapa', 'tlalixtaquilla',
            'tlapa', 'tlapehuala', 'xalpatláhuac', 'xochihuehuetlán', 'xochistlahuaca',
            'zapotitlán tablas', 'zirándaro', 'zitlala'
        ];
        
        foreach ($guerreroMunicipalities as $muni) {
            if (strpos($text, $muni) !== false) {
                return 'Guerrero';
            }
        }
        
        // Detectar por municipios conocidos de Michoacán
        $michoacanMunicipalities = [
            'morelia', 'uruapan', 'zamora', 'lázaro cárdenas', 'apatzingán', 'zitácuaro',
            'sahuayo', 'pátzcuaro', 'la piedad', 'hidalgo', 'maravatío', 'jacona',
            'jiquilpan', 'los reyes', 'paracho', 'puruándiro', 'tarímbaro',
            'yurécuaro', 'cherán', 'coalcomán', 'contepec', 'cotija', 'cuitzeo',
            'churintzio', 'ecuandureo', 'epitacio huerta', 'gabriel zamora',
            'huandacareo', 'huaniqueo', 'huetamo', 'huiramba', 'irimbo',
            'ixtlán', 'jungapeo', 'lagunillas', 'madero', 'marcos castellanos',
            'lázaro cárdenas', 'múgica', 'nahuatzen', 'nocupétaro', 'nuevo parangaricutiro',
            'nuevo urecho', 'numarán', 'ocampo', 'pajacuarán', 'panindícuaro',
            'parácuaro', 'peribán', 'la piedad', 'purépero', 'puruándiro',
            'queréndaro', 'quiroga', 'cojumatlán', 'rayon', 'vista hermosa',
            'susupuato', 'tacámbaro', 'tancítaro', 'tangamandapio', 'tangancícuaro',
            'tanhuato', 'taretan', 'tarímbaro', 'tepalcatepec', 'tingambato',
            'tingüindín', 'tiquicheo', 'tlalpujahua', 'tlazazalca', 'tocumbo',
            'tumbiscatío', 'turicato', 'tuxpan', 'tuzantla', 'tzintzuntzan',
            'tzitzio', 'uruapan', 'venustiano carranza', 'villamar', 'vista hermosa',
            'yurécuaro', 'zacapu', 'zamora', 'zináparo', 'zinapécuaro', 'ziracuaretiro', 'zitácuaro'
        ];
        
        foreach ($michoacanMunicipalities as $muni) {
            if (strpos($text, $muni) !== false) {
                return 'Michoacán de Ocampo';
            }
        }
        
        return null;
    }

    /**
     * Obtiene las iniciales de un texto
     */
    private function getInitials($text)
    {
        $words = explode(' ', $text);
        $initials = '';
        foreach ($words as $word) {
            if (strlen($word) > 0) {
                $initials .= $word[0];
            }
        }
        return $initials;
    }

    /**
     * Normaliza texto de localidad con manejo avanzado de variaciones
     * Especializado para detectar diferentes formas de escritura
     */
    private function normalizeLocationText($text)
    {
        $text = trim(strtolower($text));
        // 1. Remover acentos
        $text = $this->removeAccents($text);
        // 2. Limpiar caracteres especiales pero conservar espacios y guiones
        $text = preg_replace('/[^\w\s\-]/', ' ', $text);
        // 3. Normalizar espacios múltiples
        $text = preg_replace('/\s+/', ' ', $text);
        // 4. Remover sufijos de estado comunes al final
        $stateSuffixes = [ ' gro', ' guerrero', ' mich', ' michoacan', ' michoacán', ' mex', ' mexico', ', gro', ', guerrero', ', mich', ', michoacan', ' estado de guerrero', ' estado de michoacan', ' estado de michoacán' ];
        foreach ($stateSuffixes as $suffix) { if (str_ends_with($text, $suffix)) { $text = trim(substr($text, 0, -strlen($suffix))); break; } }
        // 5. Expandir abreviaciones / descriptores centralizado
        $text = $this->expandAbbreviations($text);
        // 6. Normalizar conectores problemáticos al inicio
        $text = preg_replace('/^(ciudad|san|santa|santo|general)\s+/', '$1 ', $text);
        // 7. Aplicar correcciones específicas conocidas
        $text = $this->applyKnownCorrections($text);
        // 8. Limpiar espacios finales
        return trim($text);
    }
    private function expandAbbreviations($text) {
        $pad = ' ' . $text . ' ';
        $map = [ ' cd. '=>' ciudad ', ' cd '=>' ciudad ', ' c. '=>' ciudad ', ' c '=>' ciudad ', ' col. '=>' colonia ', ' col '=>' colonia ', ' fracc. '=>' fraccionamiento ', ' fracc '=>' fraccionamiento ', ' ej. '=>' ejido ', ' ej '=>' ejido ', ' sn '=>' san ', ' sta. '=>' santa ', ' sta '=>' santa ', ' sto. '=>' santo ', ' sto '=>' santo ', ' gral. '=>' general ', ' gral '=>' general ', ' lic. '=>' licenciado ', ' lic '=>' licenciado ', ' prof. '=>' profesor ', ' prof '=>' profesor ', ' dr. '=>' doctor ', ' dr '=>' doctor ', ' dra. '=>' doctora ', ' dra '=>' doctora ' ];
        foreach ($map as $k=>$v) { $pad = str_replace($k,$v,$pad); }
        $text = trim(preg_replace('/\s+/', ' ', $pad));
        $regex = [ '/^col\s+/' => 'colonia ', '/^cd\s+/' => 'ciudad ', '/^fracc\s+/' => 'fraccionamiento ' ];
        foreach ($regex as $p=>$r) { $text = preg_replace($p,$r,$text); }
        return $text;
    }
    /**
     * Aplica correcciones específicas conocidas para localidades de Guerrero y Michoacán
     */
    private function applyKnownCorrections($text)
    {
        // Correcciones específicas para localidades conocidas
        $corrections = [
            // Guerrero
            'jario pantoja' => 'jario y pantoja',
            'santos reyes' => 'santos reyes nopala',
            'valle verde' => 'valle verde o valle de bravo',
            'puerto marques' => 'puerto marqués',
            'pie de la cuesta' => 'pie de la cuesta',
            'costa azul' => 'costa azul',
            'costa chica' => 'costa chica de guerrero',
            'la sabana' => 'la sabana',
            'el quemado' => 'el quemado',
            'tres palos' => 'tres palos',
            'el guayavo' => 'el guayabo',
            'guayavo' => 'guayabo',
            
            // Michoacán
            'ciudad hidalgo' => 'hidalgo',
            'lazaro cardenas' => 'lázaro cárdenas',
            'nueva italia' => 'nueva italia de ruiz',
            'los reyes' => 'los reyes de salgado',
            'la piedad' => 'la piedad de cabadas',
            'vista hermosa' => 'vista hermosa de negrete',
            'gabriel zamora' => 'gabriel zamora',
            'nuevo parangaricutiro' => 'nuevo parangaricutiro',
            'nuevo urecho' => 'nuevo urecho',
            
            // Conectores comunes que faltan
            'san jose' => 'san josé',
            'santa maria' => 'santa maría',
            'santo tomas' => 'santo tomás',
            'puerto escondido' => 'puerto escondido',
            'agua blanca' => 'agua blanca',
            'tierra colorada' => 'tierra colorada',
            'campo morado' => 'campo morado',
            'rancho viejo' => 'rancho viejo',
            'loma bonita' => 'loma bonita',
            'cerro azul' => 'cerro azul',
            'monte alto' => 'monte alto',
            'rio grande' => 'río grande',
            'paso morelos' => 'paso de morelos',
            'cruz grande' => 'cruz grande',
            'agua fria' => 'agua fría',
        ];
        
        // Aplicar correcciones exactas
        if (isset($corrections[$text])) {
            return $corrections[$text];
        }
        
        // Aplicar correcciones parciales (buscar patrones)
        foreach ($corrections as $pattern => $replacement) {
            if (strpos($text, $pattern) !== false) {
                $text = str_replace($pattern, $replacement, $text);
            }
        }
        
        // Patrones para agregar conectores faltantes comunes
        $patterns = [
            // Patrón: "palabra1 palabra2" -> "palabra1 y palabra2" (si ambas son nombres propios)
            '/^([a-z]+)\s+([a-z]+)$/' => function($matches) {
                $word1 = $matches[1];
                $word2 = $matches[2];
                
                // Lista de primeras palabras que típicamente van con "y"
                $needsY = ['jario', 'santos', 'santa', 'san', 'nuevo', 'nueva', 'puerto', 'cerro', 'loma', 'monte'];
                
                if (in_array($word1, $needsY) || 
                    (strlen($word1) >= 4 && strlen($word2) >= 4)) {
                    return $word1 . ' y ' . $word2;
                }
                
                return $matches[0];
            },
            
            // Patrón para "ciudad + nombre"
            '/^ciudad\s+(.+)$/' => 'ciudad de $1',
            
            // Patrón para "puerto + nombre"
            '/^puerto\s+([a-z]+)$/' => 'puerto $1',
        ];
        
        foreach ($patterns as $pattern => $replacement) {
            if (is_callable($replacement)) {
                $text = preg_replace_callback($pattern, $replacement, $text);
            } else {
                $text = preg_replace($pattern, $replacement, $text);
            }
        }
        
        return $text;
    }

    /**
     * Verifica si el texto es solo el nombre de un estado
     */
    private function isOnlyStateName($text)
    {
        $stateNames = [
            'guerrero', 'gro',
            'michoacan', 'michoacán', 'mich',
            'mexico', 'mex'
        ];
        
        return in_array(strtolower(trim($text)), $stateNames);
    }

    /**
     * Obtiene el nombre completo del estado
     */
    private function getFullStateName($text)
    {
        $stateMapping = [
            'guerrero' => 'Guerrero',
            'gro' => 'Guerrero',
            'michoacan' => 'Michoacán de Ocampo',
            'michoacán' => 'Michoacán de Ocampo',
            'mich' => 'Michoacán de Ocampo',
            'mexico' => 'México',
            'mex' => 'México'
        ];
        
        return $stateMapping[strtolower(trim($text))] ?? null;
    }

    /**
     * Remueve acentos de un texto
     */
    private function removeAccents($text)
    {
        $accents = [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ā' => 'a', 'ã' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e', 'ē' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i', 'ī' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'ō' => 'o', 'õ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u', 'ū' => 'u',
            'ñ' => 'n', 'ç' => 'c'
        ];
        
        return strtr($text, $accents);
    }

    /**
     * Búsqueda manual en base de datos como fallback
     */
    private function searchLocationManually($locationText)
    {
        // Buscar en tabla locations directamente
        $location = Location::with(['municipality.state'])
            ->where('name', 'ILIKE', $locationText)
            ->orWhere('name', 'ILIKE', "%{$locationText}%")
            ->first();
            
        if ($location) {
            return [
                'id' => $location->id,
                'name' => $location->name,
                'display_text' => $location->name . ' - ' . $location->municipality->name . ', ' . $location->municipality->state->name,
                'municipality_id' => $location->municipality_id,
                'municipality_name' => $location->municipality->name,
                'state_id' => $location->municipality->state->id,
                'state_name' => $location->municipality->state->name,
            ];
        }

        return null;
    }

    /**
     * Construye una query SQL para búsqueda normalizada sin acentos
     */
    private function buildNormalizedSearchQuery($column)
    {
        // Query SQL que normaliza el campo de la BD para búsqueda con LIKE
        return "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE($column, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n'), 'à', 'a'), 'è', 'e'), 'ì', 'i'), 'ò', 'o'), 'ù', 'u'), 'ç', 'c')) LIKE ?";
    }

    /**
     * Búsqueda parcial amplia: busca todas las palabras significativas dentro de priority_locations
     * y devuelve el mejor match por similitud (>0.7) si no se encontró antes.
     */
    private function broadPartialLocationSearch($searchKey)
    {
        $words = array_filter(explode(' ', $searchKey), fn($w) => strlen($w) >= 3);
        if (empty($words)) return null;
        $priorityStates = ['Guerrero', 'Michoacán de Ocampo'];
        $attempts = [true, false];
        foreach ($attempts as $priorityOnly) {
            $query = DB::table('priority_locations');
            if ($priorityOnly) { $query->whereIn('state_name', $priorityStates); }
            foreach ($words as $w) { $query->where('normalized_name', 'LIKE', '%' . $w . '%'); }
            $candidates = $query->limit(25)->get();
            $best = null; $bestScore = 0; $bestRaw = 0;
            foreach ($candidates as $c) {
                $rawSim = $this->calculateAdvancedSimilarity($searchKey, $c->normalized_name);
                $bonus = $this->getStatePriorityBonus($c->state_name);
                $score = min(1.0, $rawSim + $bonus);
                if ($score > $bestScore) { $bestScore = $score; $best = $c; $bestRaw = $rawSim; }
            }
            $threshold = $priorityOnly ? 0.68 : 0.7;
            if ($best && $bestScore >= $threshold) {
                return [
                    'id' => $best->location_id,
                    'name' => $best->location_name,
                    'municipality' => $best->municipality_name,
                    'state' => $best->state_name,
                    'suggestion' => $bestScore < 0.85,
                    'confidence' => $bestScore >= 0.85 ? 'high' : 'medium',
                    'similarity' => $bestScore,
                    'raw_similarity' => $bestRaw,
                    'state_bonus' => $this->getStatePriorityBonus($best->state_name),
                    'priority_phase' => $priorityOnly ? 'priority_states' : 'all_states'
                ];
            }
        }
        return null;
    }
    private function getStatePriorityBonus($stateName)
    {
        if (!$stateName) return 0.0;
        $state = strtolower($stateName);
        if ($state === 'guerrero') return 0.05;
        if (in_array($state, ['michoacán de ocampo','michoacan de ocampo','michoacán de ocompo'])) return 0.04;
        return 0.0;
    }

    /**
     * Intento secundario: si se detectó municipio o estado, intentar mapear a una localidad existente
     * usando búsqueda relajada dentro del municipio y comparando similitud.
     */
    private function resolveLocationFromMunicipality($originalText, $municipalityName = null, $stateName = null)
    {
        if (!$municipalityName && !$stateName) return null;

        $normOriginal = strtolower($this->normalizeLocationText($originalText));

        $locationsQuery = Location::with(['municipality.state']);
        if ($municipalityName) {
            $locationsQuery->whereHas('municipality', function($q) use ($municipalityName) {
                $q->whereRaw("LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, 'á','a'), 'é','e'), 'í','i'), 'ó','o'), 'ú','u')) = ?", [strtolower($this->removeAccents($municipalityName))]);
            });
        } elseif ($stateName) {
            $locationsQuery->whereHas('municipality.state', function($q) use ($stateName) {
                $q->whereRaw("LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, 'á','a'), 'é','e'), 'í','i'), 'ó','o'), 'ú','u')) = ?", [strtolower($this->removeAccents($stateName))]);
            });
        }

        $candidates = $locationsQuery->limit(100)->get();
        $best = null;
        $bestScore = 0;
        foreach ($candidates as $cand) {
            $candNorm = strtolower($this->normalizeLocationText($cand->name));
            $score = $this->calculateAdvancedSimilarity($normOriginal, $candNorm);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $cand;
            }
        }
        // Umbral relajado (>=0.78) para aceptar
        if ($best && $bestScore >= 0.78) {
            return [
                'id' => $best->id,
                'name' => $best->name,
                'municipality' => $best->municipality->name,
                'state' => $best->municipality->state->name,
                'similarity' => $bestScore
            ];
        }
        return null;
    }
}