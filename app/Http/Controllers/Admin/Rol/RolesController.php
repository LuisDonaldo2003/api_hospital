<?php

namespace App\Http\Controllers\Admin\Rol;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;

class RolesController extends Controller
{
    public function index(Request $request)
    {
        $query = Role::query();

        if ($request->filled('search')) {
            $query->where("name", "like", "%" . $request->search . "%");
        }

        $roles = $query->orderBy("id", "desc")->with('permissions')->get();

        return response()->json([
            "roles" => $roles->map(function ($rol) {
                return [
                    "id" => $rol->id,
                    "name" => $rol->name,
                    "permissions" => $rol->permissions,
                    "permission_pluck" => $rol->permissions->pluck("name"),
                    "created_at" => $rol->created_at->format("Y-m-d h:i:s")
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        if (Role::where("name", $request->name)->exists()) {
            return response()->json([
                "message" => 403,
                "message_text" => "EL NOMBRE DEL ROL YA EXISTE"
            ], 403);
        }

        $role = Role::create([
            'guard_name' => 'api',
            'name' => $request->name,
        ]);

        if (is_array($request->permissions)) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json([
            "message" => 200,
            "message_text" => "Rol creado correctamente",
            "role" => [
                "id" => $role->id,
                "name" => $role->name,
                "permissions" => $role->permissions->pluck("name")
            ]
        ], 201);
    }

    public function show(string $id)
    {
        $role = Role::with('permissions')->findOrFail($id);

        return response()->json([
            "id" => $role->id,
            "name" => $role->name,
            "permissions" => $role->permissions,
            "permission_pluck" => $role->permissions->pluck("name"),
            "created_at" => $role->created_at->format("Y-m-d h:i:s")
        ]);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        $role = Role::findOrFail($id);
        $role->update(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        $role->load('permissions');

        return response()->json([
            "message" => 200,
            "message_text" => "Rol actualizado correctamente",
            "role" => [
                "id" => $role->id,
                "name" => $role->name,
                "permissions" => $role->permissions,
                "permission_pluck" => $role->permissions->pluck("name"),
                "created_at" => $role->created_at->format("Y-m-d h:i:s")
            ]
        ], 200);
    }

    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);

        if ($role->users && $role->users->count() > 0) {
            return response()->json([
                "message" => 403,
                "message_text" => "EL ROL SELECCIONADO NO SE PUEDE ELIMINAR PORQUE TIENE USUARIOS RELACIONADOS"
            ], 403);
        }

        $role->delete();

        return response()->json([
            "message" => 200,
            "message_text" => "Rol eliminado correctamente"
        ]);
    }
}
