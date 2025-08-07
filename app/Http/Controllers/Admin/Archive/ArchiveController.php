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
                $nameWords = explode(' ', strtolower($name));
                $query->where(function ($q) use ($nameWords) {
                    foreach ($nameWords as $word) {
                        $q->where(function ($subQ) use ($word) {
                            $subQ->whereRaw('LOWER(name) LIKE ?', ["%$word%"])
                                 ->orWhereRaw('LOWER(last_name_father) LIKE ?', ["%$word%"])
                                 ->orWhereRaw('LOWER(last_name_mother) LIKE ?', ["%$word%"]);
                        });
                    }
                });
            }
        }

        // Filtro por género
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

        // LÓGICA SIMPLIFICADA: Si se envía location_name pero no location_id
        if (!$request->location_id && $request->location_name) {
            $locationName = trim($request->location_name);
            
            // Intento 1: Buscar usando la función inteligente de localidades
            $foundLocation = $this->findOrCreateLocationIntelligently($locationName);
            
            if ($foundLocation && isset($foundLocation['id'])) {
                // Si existe, usar esa localidad y limpiar location_text
                $data['location_id'] = $foundLocation['id'];
                $data['location_text'] = null;
            } else {
                // Si no encuentra la localidad, guardar solo el texto plano
                $data['location_id'] = null;
                $data['location_text'] = $locationName;
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
        return Storage::disk('public')->download($path);
    }

    /**
     * Endpoint de prueba para verificar mapeo de localidades
     */
    public function testLocationMapping(Request $request)
    {
        $request->validate([
            'location_text' => 'required|string|max:255'
        ]);

        $locationText = trim($request->location_text);
        
        try {
            $mappedLocation = $this->findOrCreateLocationIntelligently($locationText);
            
            return response()->json([
                'success' => true,
                'original_text' => $locationText,
                'mapped_location' => $mappedLocation,
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
     * Mapeo inteligente para localidades con entrada de texto plano
     * Maneja las variaciones comunes encontradas en datos legacy como hospital.sql
     */
    private function findOrCreateLocationIntelligently($locationText)
    {
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
            'nuevo galeana' => 'nuevo galeana',
            'cerro verde' => 'cerro verde',
            'las querendas' => 'las querendas',
            'puerto del oro' => 'puerto del oro',
            'san cristobal' => 'san cristóbal',
            'el timbiriche' => 'el timbiriche',
            'san antonio de las huertas' => 'san antonio de las huertas',
            'arroyo grande' => 'arroyo grande',
            'tanganhuato' => 'tanganhuato',
            'san juan mina' => 'san juan mina',
            'chacamero grande' => 'chacamero grande',
            'placeres del oro' => 'placeres del oro',
            'san mateo' => 'san mateo',
            
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
        $searchKey = strtolower($normalizedLocation);
        if (isset($knownMappings[$searchKey])) {
            $normalizedLocation = $knownMappings[$searchKey];
        }

        // Intentar encontrar la localidad usando la API de búsqueda inteligente
        $locationController = app(\App\Http\Controllers\Admin\Location\LocationController::class);
        
        // Crear request simulado para usar el método autoDetectLocation
        $fakeRequest = new \Illuminate\Http\Request(['search' => $normalizedLocation]);
        $result = $locationController->autoDetectLocation($fakeRequest);
        $resultData = $result->getData();

        if ($resultData->success && isset($resultData->location)) {
            return [
                'id' => $resultData->location->id,
                'name' => $resultData->location->name,
                'display_text' => $resultData->location->display_text,
                'municipality_id' => $resultData->location->municipality_id,
                'municipality_name' => $resultData->location->municipality_name,
                'state_id' => $resultData->location->state_id,
                'state_name' => $resultData->location->state_name,
            ];
        }

        // Si no se encuentra con auto-detección, buscar manualmente en BD
        return $this->searchLocationManually($normalizedLocation);
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
        
        // Expandir abreviaciones básicas
        $abbreviations = [
            'cd' => 'ciudad',
            'c' => 'ciudad',
            'sn' => 'san',
            'sta' => 'santa',
            'sto' => 'santo',
        ];
        
        $words = explode(' ', $text);
        $expandedWords = [];
        
        foreach ($words as $index => $word) {
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
}