<?php

namespace App\Http\Controllers\Admin\Departament;

use App\Http\Controllers\Controller;
use App\Models\Departaments;
use Illuminate\Http\Request;
use App\Services\ActivityLoggerService;

class DepartamentController extends Controller
{
    public function index(Request $request)
    {
        $query = Departaments::query();

        if ($request->filled('search')) {
            $query->where("name", "like", "%" . $request->search . "%");
        }

        $departaments = $query->orderBy("id", "desc")->get();

        // Log the list action
        ActivityLoggerService::logRead('Departament', null, 'departaments', [
            'search_term' => $request->search,
            'total_results' => $departaments->count()
        ]);

        return response()->json([
            "departaments" => $departaments->map(function ($departament) {
                return [
                    "id" => $departament->id,
                    "name" => $departament->name,
                    "state" => $departament->state,
                    "created_at" => $departament->created_at->format("Y-m-d h:i:s")
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'state' => 'required|boolean',
        ]);

        if (Departaments::where("name", $request->name)->exists()) {
            return response()->json([
                "message" => 403,
                "message_text" => "EL NOMBRE DEL DEPARTAMENTO YA EXISTE"
            ]);
        }

        $departaments = Departaments::create($request->all());

        // Log the creation activity
        ActivityLoggerService::logCreate('Departament', $departaments->id, 'departaments', [
            'name' => $departaments->name,
            'state' => $departaments->state
        ]);

        return response()->json([
            "message" => 200,
            "message_text" => "Departamento creado correctamente",
            "departament" => [
                "id" => $departaments->id,
                "name" => $departaments->name,
                "state" => $departaments->state,
            ]
        ]);
    }

    public function show(string $id)
    {
        $departament = Departaments::findOrFail($id);

        // Log the read activity
        ActivityLoggerService::logRead('Departament', $departament->id, 'departaments', [
            'name' => $departament->name,
            'state' => $departament->state
        ]);

        return response()->json([
            "id" => $departament->id,
            "name" => $departament->name,
            "state" => $departament->state
        ]);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'state' => 'required|boolean',
        ]);

        if (Departaments::where("id", "<>", $id)->where("name", $request->name)->exists()) {
            return response()->json([
                "message" => 403,
                "message_text" => "EL NOMBRE DEL DEPARTAMENTO YA EXISTE"
            ]);
        }

        $departament = Departaments::findOrFail($id);

        // Store old values for logging
        $oldValues = [
            'name' => $departament->name,
            'state' => $departament->state
        ];

        $departament->update($request->all());

        // Store new values for logging
        $newValues = [
            'name' => $departament->name,
            'state' => $departament->state
        ];

        // Log the update activity
        ActivityLoggerService::logUpdate('Departament', $departament->id, 'departaments', $oldValues, $newValues);

        return response()->json([
            "message" => 200,
            "message_text" => "Departamento actualizado correctamente",
            "departament" => [
                "id" => $departament->id,
                "name" => $departament->name,
                "state" => $departament->state,
            ]
        ]);
    }

    public function destroy(string $id)
    {
        $departament = Departaments::findOrFail($id);

        // Log the deletion activity
        ActivityLoggerService::logDelete('Departament', $departament->id, 'departaments', [
            'name' => $departament->name,
            'state' => $departament->state
        ]);

        $departament->delete();

        return response()->json([
            "message" => 200,
            "message_text" => "Departamento eliminado correctamente"
        ]);
    }
}
