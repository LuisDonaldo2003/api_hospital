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
use App\Services\ActivityLoggerService;

class ArchiveController extends Controller
{
    public function index(Request $request)
    {
        $query = Archive::with(['gender']);
            
        // Default sort desc unless specified
        $sortDir = $request->input('sort_dir', 'asc');
        // Validate sort direction
        if (!in_array(strtolower($sortDir), ['asc', 'desc'])) {
            $sortDir = 'asc';
        }
        
        $query->orderBy('archive_number', $sortDir);

        // Variable para almacenar el número de expediente buscado originalmente
        $originalSearchNumber = null;
        
        // Filtro por número de expediente
        if ($request->filled('archive_number')) {
            $archiveNumber = trim($request->archive_number);
            
            // Si es numérico y parece búsqueda exacta (sin wildcards)
            if (is_numeric($archiveNumber) && !str_contains($archiveNumber, '%')) {
                $baseNumber = (int) $archiveNumber;
                $originalSearchNumber = $baseNumber;
                
                // Buscar el expediente específico + los 10 SIGUIENTES REGISTROS QUE EXISTAN
                // No usar rango fijo, sino buscar los siguientes registros existentes
                $query->where('archive_number', '>=', $baseNumber)
                     ->limit(11); // 1 original + 10 siguientes
            } else {
                // Búsqueda parcial normal (para texto o búsquedas con wildcards)
                $query->where('archive_number', 'ilike', '%' . $archiveNumber . '%');
            }
        }

        // Filtro por nombre (búsqueda mejorada en nombre completo)
        if ($request->filled('name')) {
            $name = trim($request->name);
            if ($name !== '') {
                // Normalizar el texto de búsqueda (sin acentos y en minúsculas)
                $normalizedName = $this->removeAccents(strtolower($name));
                $nameWords = explode(' ', $normalizedName);
                
                $query->where(function ($q) use ($nameWords, $name) {
                    // Búsqueda palabra por palabra normalizada
                    foreach ($nameWords as $word) {
                        if (trim($word) !== '') {
                            $q->where(function ($subQ) use ($word) {
                                // Búsqueda sin acentos y case-insensitive en todos los campos de nombre
                                $subQ->whereRaw($this->buildNormalizedSearchQuery('name'), ["%$word%"])
                                     ->orWhereRaw($this->buildNormalizedSearchQuery('last_name_father'), ["%$word%"])
                                     ->orWhereRaw($this->buildNormalizedSearchQuery('last_name_mother'), ["%$word%"])
                                     // Búsqueda en nombre completo concatenado (PostgreSQL)
                                     ->orWhereRaw("LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(name, '') || ' ' || COALESCE(last_name_father, '') || ' ' || COALESCE(last_name_mother, ''), 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n'), 'à', 'a'), 'è', 'e'), 'ì', 'i'), 'ò', 'o'), 'ù', 'u'), 'ç', 'c')) ILIKE ?", ["%$word%"]);
                            });
                        }
                    }
                    
                    // Búsqueda adicional más simple como fallback
                    $q->orWhere(function ($fallbackQ) use ($name) {
                        $fallbackQ->where('name', 'ILIKE', "%$name%")
                                  ->orWhere('last_name_father', 'ILIKE', "%$name%")
                                  ->orWhere('last_name_mother', 'ILIKE', "%$name%")
                                  ->orWhereRaw("(COALESCE(name, '') || ' ' || COALESCE(last_name_father, '') || ' ' || COALESCE(last_name_mother, '')) ILIKE ?", ["%$name%"]);
                    });
                });
            }
        }
        
        // Filtro por género
        if ($request->filled('gender_id')) {
            $query->where('gender_id', $request->gender_id);
        }

        // Filtros de ubicación usando campos de texto
        if ($request->filled('state_text')) {
            $query->where('state_text', 'ilike', '%' . $request->state_text . '%');
        }

        if ($request->filled('municipality_text')) {
            $query->where('municipality_text', 'ilike', '%' . $request->municipality_text . '%');
        }

        if ($request->filled('location_text')) {
            $query->where('location_text', 'ilike', '%' . $request->location_text . '%');
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
            
            // Marcar el expediente buscado originalmente para resaltarlo en el frontend
            if ($originalSearchNumber !== null) {
                $archives->each(function($archive) use ($originalSearchNumber) {
                    $archive->is_original_search = ($archive->archive_number == $originalSearchNumber);
                });
            }

            return response()->json([
                'data' => $archives,
                'total' => $total,
                'current_page' => floor($skip / $limit) + 1,
                'per_page' => $limit,
                'original_search_number' => $originalSearchNumber
            ]);
        }

        // Fallback con paginación por defecto
        // Log de acceso a listado
        ActivityLoggerService::logRead('Archive', null, 'archive', [
            'filters_applied' => !empty(array_filter($request->only(['archive_number', 'name', 'surname', 'birth_date', 'gender_id']))),
            'total_results' => $query->count()
        ]);

        return response()->json($query->paginate(50));
    }



    public function show($id)
    {
        $archive = Archive::with(['gender'])->find($id);

        if (!$archive) {
            return response()->json(['message' => 'Registro no encontrado.'], 404);
        }

        // Registrar actividad de visualización
        ActivityLoggerService::logRead('Archive', $archive->id, 'archive', [
            'name' => $archive->name ?? 'N/A',
            'last_name_father' => $archive->last_name_father ?? 'N/A',
            'archive_number' => $archive->archive_number ?? 'N/A'
        ]);

        return response()->json(['archive' => $archive]);
    }

    public function update(Request $request, $id)
    {
        $archive = Archive::find($id);

        if (!$archive) {
            return response()->json(['message' => 'Registro no encontrado.'], 404);
        }

        $request->validate([
            'archive_number' => 'required|integer', // Removed 'unique' rule here to do manual check
            'last_name_father' => 'nullable|string|max:100',
            'last_name_mother' => 'nullable|string|max:100',
            'name' => 'nullable|string|max:100',
            'age' => 'nullable|integer',
            'age_unit' => 'nullable|string|in:años,días,meses',
            'gender_id' => 'nullable|integer|exists:genders,id',
            'contact_last_name_father' => 'nullable|string|max:100',
            'contact_last_name_mother' => 'nullable|string|max:100',
            'contact_name' => 'nullable|string|max:100',
            'admission_date' => 'nullable|date',
            'address' => 'nullable|string|max:150',
            'location_text' => 'nullable|string|max:150',
            'municipality_text' => 'nullable|string|max:150',
            'state_text' => 'nullable|string|max:100',
        ]);

        // Verificación MANUAL de duplicados (Postgres compatible) excluyendo el actual
        $proposedNumber = (int) $request->archive_number;
        $exists = \DB::table('archive')
            ->whereRaw('CAST(archive_number AS INTEGER) = ?', [$proposedNumber])
            ->where('archive_number', '!=', $id) // Excluir el propio registro
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'El número de expediente ya está en uso por otro paciente.'], 422);
        }

        // Guardar valores anteriores para el log
        $oldValues = [
            'name' => $archive->name ?? 'N/A',
            'last_name_father' => $archive->last_name_father ?? 'N/A',
            'archive_number' => $archive->archive_number ?? 'N/A'
        ];
        
        $archive->update($request->all());
        
        // Registrar actividad de actualización
        ActivityLoggerService::logUpdate('Archive', $archive->id, 'archive', $oldValues, [
            'name' => $archive->name ?? 'N/A',
            'last_name_father' => $archive->last_name_father ?? 'N/A',
            'archive_number' => $archive->archive_number ?? 'N/A'
        ]);

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

        // Registrar actividad de eliminación antes de eliminar
        ActivityLoggerService::logDelete('Archive', $archive->id, 'archive', [
            'name' => $archive->name ?? 'N/A',
            'last_name_father' => $archive->last_name_father ?? 'N/A',
            'archive_number' => $archive->archive_number ?? 'N/A',
            'birth_date' => $archive->birth_date ?? 'N/A',
            'gender' => $archive->gender ?? 'N/A'
        ]);
        
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

        // Top 5 localidades últimos 7 días (usando campos de texto)
        $topLocations = Archive::select('location_text', 'municipality_text', 'state_text', DB::raw('count(*) as total'))
            ->whereNotNull('location_text')
            ->whereRaw($dateExprStr . ' BETWEEN ? AND ?', [$weekAgo->toDateString(), $today->toDateString()])
            ->groupBy('location_text', 'municipality_text', 'state_text')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $locationData = [];
        foreach ($topLocations as $row) {
            $display = $row->location_text ?: 'Sin localidad';
            if ($row->municipality_text && $row->state_text) {
                $display .= ' (' . $row->municipality_text . ', ' . $row->state_text . ')';
            } elseif ($row->municipality_text) {
                $display .= ' (' . $row->municipality_text . ')';
            } elseif ($row->state_text) {
                $display .= ' (' . $row->state_text . ')';
            }
            $locationData[] = [
                'name' => $display,
                'count' => (int) $row->total
            ];
        }

        // Conteos por mes (año actual) y por año (histórico)
        $currentYear = now()->year;

        // Postgres: agrupar por mes del año actual
        $monthlyRaw = DB::table('archive')
            ->select(
                DB::raw("TO_CHAR(DATE_TRUNC('month', COALESCE(admission_date, created_at)), 'YYYY-MM') as ym"),
                DB::raw('COUNT(*) as total')
            )
            ->whereRaw("DATE_PART('year', COALESCE(admission_date, created_at)) = ?", [$currentYear])
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->keyBy('ym');

        $monthlyCounts = [];
        for ($m = 1; $m <= 12; $m++) {
            $key = sprintf('%04d-%02d', $currentYear, $m);
            $monthlyCounts[] = [
                'year' => $currentYear,
                'month' => $m,
                'count' => (int) ($monthlyRaw[$key]->total ?? 0)
            ];
        }

        // Postgres: agrupar por año histórico
        $yearlyRows = DB::table('archive')
            ->select(DB::raw("DATE_PART('year', COALESCE(admission_date, created_at))::int as y"), DB::raw('COUNT(*) as total'))
            ->groupBy('y')
            ->orderBy('y')
            ->get();

        $yearlyCounts = [];
        foreach ($yearlyRows as $row) {
            // Puede haber registros sin fecha; filtrar nulos
            if ($row->y !== null) {
                $yearlyCounts[] = [ 'year' => (int) $row->y, 'count' => (int) $row->total ];
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
            // Nuevos bloques
            'monthlyCounts' => $monthlyCounts,
            'yearlyCounts' => $yearlyCounts,
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
     * Construye una query SQL para búsqueda normalizada sin acentos
     */
    private function buildNormalizedSearchQuery($column)
    {
        // Query SQL que normaliza el campo de la BD para búsqueda con ILIKE (case-insensitive en PostgreSQL)
        return "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE($column, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n'), 'à', 'a'), 'è', 'e'), 'ì', 'i'), 'ò', 'o'), 'ù', 'u'), 'ç', 'c')) ILIKE ?";
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
     * Obtiene el siguiente número de expediente disponible y lista de espacios vacíos
     */
    public function nextNumber()
    {
        $next = $this->getNextArchiveNumber();

        return response()->json([
            'next_number' => $next,
            'available_gaps' => [] 
        ]);
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
            'age_unit' => 'nullable|string|in:años,días,meses',
            'gender_id' => 'nullable|integer|exists:genders,id',
            'contact_last_name_father' => 'nullable|string|max:100',
            'contact_last_name_mother' => 'nullable|string|max:100',
            'contact_name' => 'nullable|string|max:100',
            'admission_date' => 'nullable|date',
            'address' => 'nullable|string|max:150',
            'location_text' => 'nullable|string|max:150',
            'municipality_text' => 'nullable|string|max:100',
            'state_text' => 'nullable|string|max:100',
        ], [
            'archive_number.unique' => 'El número de expediente ' . $request->archive_number . ' ya existe. Por favor, use un número diferente.',
            'archive_number.required' => 'El número de expediente es obligatorio.',
            'archive_number.integer' => 'El número de expediente debe ser un número entero.'
        ]);

        // -------------------------------

        $archive = Archive::create($request->all());

        // Debug logs
        \Log::info('ArchiveController: Nuevo archivo creado', [
            'archive_id' => $archive->id,
            'user_authenticated' => \Illuminate\Support\Facades\Auth::check(),
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'user_name' => \Illuminate\Support\Facades\Auth::user()?->name ?? 'No user'
        ]);

        // Registrar actividad
        ActivityLoggerService::logCreate('Archive', $archive->id, 'archive', [
            'name' => $archive->name ?? 'N/A',
            'last_name_father' => $archive->last_name_father ?? 'N/A',
            'last_name_mother' => $archive->last_name_mother ?? 'N/A',
            'archive_number' => $archive->archive_number ?? 'N/A',
            'birth_date' => $archive->birth_date ?? 'N/A',
            'gender_id' => $archive->gender_id ?? 'N/A'
        ]);

        \Log::info('ArchiveController: ActivityLoggerService llamado');

        return response()->json([
            'message' => 'Registro creado correctamente.',
            'archive' => $archive
        ], 201);
    }

    /**
     * Verifica si un número de expediente ya existe O si rompe la secuencia (salto grande).
     */
    public function checkUnique(Request $request)
    {
        $request->validate([
            'archive_number' => 'required|integer'
        ]);

        $incomingNumber = (int) $request->archive_number;

        // 1. Verificar si ya existe
        $exists = \DB::table('archive')
                    ->whereRaw('CAST(archive_number AS INTEGER) = ?', [$incomingNumber])
                    ->exists();
        
        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'El número de expediente ya está en uso por otro paciente.'
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Disponible'
        ]);
    }
    /**
     * Calcula el siguiente número de expediente basado estrictamente en la configuración.
     * Busca el primer hueco disponible a partir del número configurado.
     */
    private function getNextArchiveNumber()
    {
        // 1. Obtener Configuración
        $configStart = \DB::table('configurations')->where('key', 'archive_start_number')->value('value');
        $configStart = $configStart ? (int)$configStart : 0;

        // 2. Verificar si el número de inicio existe
        // Usamos CAST para asegurar compatibilidad si la columna es string/varchar
        $existsStart = \DB::table('archive')
                        ->whereRaw('CAST(archive_number AS INTEGER) = ?', [$configStart])
                        ->exists();

        if (!$existsStart) {
            return $configStart;
        }

        // 3. Buscar el primer hueco (Gap) a partir del inicio configurado
        // Buscamos el menor número X >= Config tal que X+1 no existe.
        // Entonces el siguiente permitido es X+1.
        
        $sql = "
            SELECT MIN(CAST(archive_number AS INTEGER) + 1) as next_val
            FROM archive A
            WHERE CAST(archive_number AS INTEGER) >= ?
            AND NOT EXISTS (
                SELECT 1 FROM archive B 
                WHERE CAST(B.archive_number AS INTEGER) = CAST(A.archive_number AS INTEGER) + 1
            )
        ";

        $result = \DB::select($sql, [$configStart]);
        
        if ($result && isset($result[0]->next_val)) {
            return (int)$result[0]->next_val;
        }

        // Fallback (no debería ocurrir si existe el start, pero por seguridad)
        return $configStart + 1;
    }
}

