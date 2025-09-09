<?php

namespace App\Http\Controllers\Admin\Profile;

use App\Models\Profile;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\ActivityLoggerService;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $query = Profile::query();

        if ($request->filled('search')) {
            $query->where("name", "like", "%" . $request->search . "%");
        }

        $profiles = $query->orderBy("id", "desc")->get();

        // Log the list action
        ActivityLoggerService::logRead('Profile', null, 'profiles', [
            'search_term' => $request->search,
            'total_results' => $profiles->count()
        ]);

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

        // Log the creation activity
        ActivityLoggerService::logCreate('Profile', $profile->id, 'profiles', [
            'name' => $profile->name,
            'state' => $profile->state
        ]);

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

        // Log the read activity
        ActivityLoggerService::logRead('Profile', $profile->id, 'profiles', [
            'name' => $profile->name,
            'state' => $profile->state
        ]);

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
        
        // Store old values for logging
        $oldValues = [
            'name' => $profile->name,
            'state' => $profile->state
        ];
        
        $profile->update($request->only(['name', 'state']));

        // Log the update activity
        ActivityLoggerService::logUpdate('Profile', $profile->id, 'profiles', $oldValues, [
            'name' => $profile->name,
            'state' => $profile->state
        ]);

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
        
        // Log the deletion activity
        ActivityLoggerService::logDelete('Profile', $profile->id, 'profiles', [
            'name' => $profile->name,
            'state' => $profile->state
        ]);
        
        $profile->delete();

        return response()->json([
            "message" => 200,
            "message_text" => "Perfil eliminado correctamente"
        ]);
    }
}
