<?php

namespace App\Http\Controllers\Admin\Departament;

use App\Http\Controllers\Controller;
use App\Models\Departaments;
use Illuminate\Http\Request;

class DepartamentController extends Controller
{
    public function index(Request $request)
    {
        $query = Departaments::query();

        if ($request->filled('search')) {
            $query->where("name", "like", "%" . $request->search . "%");
        }

        $departaments = $query->orderBy("id", "desc")->get();

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
        $departament->update($request->all());

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
        $departament->delete();

        return response()->json([
            "message" => 200,
            "message_text" => "Departamento eliminado correctamente"
        ]);
    }
}
