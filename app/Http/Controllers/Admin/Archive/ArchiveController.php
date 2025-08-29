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

class ArchiveController extends Controller
{
    public function index(Request $request)
    {
        $query = Archive::with(['gender'])
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
        }
        
        // Filtro por género
        if ($request->filled('gender_id')) {
            $query->where('gender_id', $request->gender_id);
        }

        // Filtros de ubicación usando campos de texto
        if ($request->filled('state_text')) {
            $query->where('state_text', 'like', '%' . $request->state_text . '%');
        }

        if ($request->filled('municipality_text')) {
            $query->where('municipality_text', 'like', '%' . $request->municipality_text . '%');
        }

        if ($request->filled('location_text')) {
            $query->where('location_text', 'like', '%' . $request->location_text . '%');
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

        $archive = Archive::create($request->all());

        return response()->json([
            'message' => 'Registro creado correctamente.',
            'archive' => $archive
        ], 201);
    }

    public function show($id)
    {
        $archive = Archive::with(['gender'])->find($id);

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
        // Query SQL que normaliza el campo de la BD para búsqueda con LIKE
        return "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE($column, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n'), 'à', 'a'), 'è', 'e'), 'ì', 'i'), 'ò', 'o'), 'ù', 'u'), 'ç', 'c')) LIKE ?";
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
}
