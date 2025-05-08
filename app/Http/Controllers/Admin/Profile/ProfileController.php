<?php

namespace App\Http\Controllers\Admin\Profile;

use App\Models\Profile;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $query = Profile::query();

        if ($request->filled('search')) {
            $query->where("name", "like", "%" . $request->search . "%");
        }

        $profiles = $query->orderBy("id", "desc")->get();

        return response()->json([
            "profiles" => $profiles->map(function ($profile) {
                return [
                    "id" => $profile->id,
                    "name" => $profile->name,
                    "state" => $profile->state,
                    "created_at" => $profile->created_at->format("Y-m-d h:i:s")
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

        if (Profile::where("name", $request->name)->exists()) {
            return response()->json([
                "message" => 403,
                "message_text" => "EL PERFIL YA EXISTE"
            ]);
        }

        $profile = Profile::create($request->only(['name', 'state']));

        return response()->json([
            "message" => 200,
            "message_text" => "Perfil creado correctamente",
            "profile" => [
                "id" => $profile->id,
                "name" => $profile->name,
                "state" => $profile->state,
            ]
        ]);
    }

    public function show(string $id)
    {
        $profile = Profile::findOrFail($id);

        return response()->json([
            "id" => $profile->id,
            "name" => $profile->name,
            "state" => $profile->state
        ]);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'state' => 'required|boolean',
        ]);

        if (Profile::where("id", "<>", $id)->where("name", $request->name)->exists()) {
            return response()->json([
                "message" => 403,
                "message_text" => "EL PERFIL YA EXISTE"
            ]);
        }

        $profile = Profile::findOrFail($id);
        $profile->update($request->only(['name', 'state']));

        return response()->json([
            "message" => 200,
            "message_text" => "Perfil actualizado correctamente",
            "profile" => [
                "id" => $profile->id,
                "name" => $profile->name,
                "state" => $profile->state,
            ]
        ]);
    }

    public function destroy(string $id)
    {
        $profile = Profile::findOrFail($id);
        $profile->delete();

        return response()->json([
            "message" => 200,
            "message_text" => "Perfil eliminado correctamente"
        ]);
    }
}
