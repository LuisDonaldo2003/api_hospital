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

    // Filtro por número de expediente
    if ($request->filled('archive_number')) {
        $query->where('archive_number', 'like', '%' . $request->archive_number . '%');
    }

    // Filtro por nombre (si no está vacío)
    $name = trim($request->input('name', ''));
    if ($name !== '') {
        $name = strtolower($name);
        $query->where(function ($q) use ($name) {
            $q->whereRaw('LOWER(name) LIKE ?', ["%$name%"])
                ->orWhereRaw('LOWER(last_name_father) LIKE ?', ["%$name%"])
                ->orWhereRaw('LOWER(last_name_mother) LIKE ?', ["%$name%"]);
        });
    }

    // Filtro por género
    if ($request->filled('gender_id')) {
        $query->where('gender_id', $request->gender_id);
    }

    // Filtro por estado (validación de relaciones anidadas para evitar errores)
    if ($request->filled('state_id')) {
        $query->whereHas('location', function ($q) use ($request) {
            $q->whereHas('municipality', function ($q2) use ($request) {
                $q2->whereHas('state', function ($q3) use ($request) {
                    $q3->where('id', $request->state_id);
                });
            });
        });
    }

    // Filtro por municipio
    if ($request->filled('municipality_id')) {
        $query->whereHas('location', function ($q) use ($request) {
            $q->whereHas('municipality', function ($q2) use ($request) {
                $q2->where('id', $request->municipality_id);
            });
        });
    }

    // Filtro por localidad
    if ($request->filled('location_id')) {
        $query->whereHas('location', function ($q) use ($request) {
            $q->where('id', $request->location_id);
        });
    }


    // ✅ Si viene all=true, retornar todos los resultados sin paginar
    if ($request->boolean('all')) {
        return response()->json([
            'data' => $query->get()
        ]);
    }

    // Paginación
    if ($request->has('skip') && $request->has('limit')) {
        $skip = (int) $request->input('skip');
        $limit = (int) $request->input('limit');

        $total = $query->count();
        $archives = $query->skip($skip)->take($limit)->get();

        return response()->json([
            'data' => $archives,
            'total' => $total
        ]);
    }

    // fallback sin paginación
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
            'trial304' => 'nullable|string|max:1'
        ]);

        $archive = Archive::create($request->all());

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
