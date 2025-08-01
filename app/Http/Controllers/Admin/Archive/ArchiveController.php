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
        ]);

        $data = $request->all();

        // Si se envió location_name pero no location_id, intentar crear o encontrar la localidad
        if (!$request->location_id && $request->location_name) {
            $locationName = trim($request->location_name);
            
            // Buscar si ya existe una localidad con ese nombre
            $location = Location::where('name', 'LIKE', $locationName)->first();
            
            if (!$location) {
                // Si no existe, crear una nueva localidad genérica
                // Se asignará a un municipio por defecto (puede ser configurado)
                $defaultMunicipalityId = 1; // Configurar según necesidades
                
                $location = Location::create([
                    'name' => $locationName,
                    'municipality_id' => $defaultMunicipalityId,
                    'status' => true
                ]);
            }
            
            $data['location_id'] = $location->id;
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
}