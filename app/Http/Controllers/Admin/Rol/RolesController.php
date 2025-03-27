<?php

namespace App\Http\Controllers\Admin\Rol;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;

class RolesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $name = $request->search;

        $roles = Role::where("name", "like", "%" . $name . "%")
            ->orderBy("id", "desc")
            ->get();

        return response()->json([
            "roles" => $roles->map(function ($rol) {
                return [
                    "id" => $rol->id,
                    "name" => $rol->name,
                    "permissions" => $rol->permissions,
                    "permission_pluck" => $rol->permissions->pluck("name"), // Asegurar que se incluya este campo
                    "created_at" => $rol->created_at->format("Y-m-d h:i:s")
                ];
            }),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validación
        $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        $is_role = Role::where("name", $request->name)->first();

        if ($is_role) {
            return response()->json([
                "message" => 403,
                "message_text" => "EL NOMBRE DEL ROL YA EXISTE"
            ], 403);
        }

        $role = Role::create([
            'guard_name' => 'api',
            'name' => $request->name,
        ]);

        // Asignar permisos si vienen
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

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $role = Role::findOrFail($id);
        return response()->json([
            "id" => $role->id,
            "name" => $role->name,
            "permissions" => $role->permissions,
            "permission_pluck" => $role->permissions->pluck("name"),
            "created_at" => $role->created_at->format("Y-m-d h:i:s")
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validación
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        $role = Role::findOrFail($id);
        $role->update([
            'name' => $request->name
        ]);

        // Sincronizar permisos solo si se envían en la solicitud
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        // Recargar los permisos actualizados
        $role->load('permissions');

        return response()->json([
            "message" => 200,
            "message_text" => "Rol actualizado correctamente",
            "role" => [
                "id" => $role->id,
                "name" => $role->name,
                "permissions" => $role->permissions,
                "permission_pluck" => $role->permissions->pluck("name"), // Asegurar que se incluyan los permisos actualizados
                "created_at" => $role->created_at->format("Y-m-d h:i:s")
            ]
        ], 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);

        if ($role->users && $role->users->count() > 0) {
            return response()->json([
                "message" => 403,
                "message_text" => "EL ROL SELECCIONADO NO SE PUEDE ELIMINAR POR MOTIVOS QUE YA TIENE USUARIOS RELACIONADOS"
            ], 403);
        }

        $role->delete();

        return response()->json([
            "message" => 200,
            "message_text" => "Rol eliminado correctamente"
        ]);
    }
}
