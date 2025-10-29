<?php

namespace App\Http\Controllers\Admin\Rol;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Services\ActivityLoggerService;

class RolesController extends Controller
{
    public function index(Request $request)
    {
        $query = Role::query();

        if ($request->filled('search')) {
            $query->where("name", "like", "%" . $request->search . "%");
        }

        $roles = $query->orderBy("id", "desc")->with('permissions')->get();

        // Log de acceso a listado de roles
        ActivityLoggerService::logRead('Role', null, 'roles', [
            'search_term' => $request->search,
            'total_results' => $roles->count()
        ]);

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

        // Registrar actividad de creación
        ActivityLoggerService::logCreate('Role', $role->id, 'roles', [
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'permissions' => $role->permissions->pluck('name')->toArray()
        ]);

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

        // Log the read activity
        ActivityLoggerService::logRead('Role', $role->id, 'roles', [
            'name' => $role->name,
            'permissions_count' => $role->permissions->count()
        ]);

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
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:roles,name,' . $id,
                'permissions' => 'nullable|array',
                'permissions.*' => 'string|exists:permissions,name'
            ]);

            $role = Role::findOrFail($id);
            
            // Verificar que no se esté intentando editar los roles de Director o Subdirector por otros usuarios
            if (in_array($role->name, ['Director General', 'Subdirector General'])) {
                $user = auth()->user();
                if (!$user->hasRole(['Director General', 'Subdirector General'])) {
                    return response()->json([
                        "message" => 403,
                        "message_text" => "No tienes permisos para editar este rol administrativo"
                    ], 403);
                }
            }
            
            // Guardar valores anteriores para el log
            $oldValues = [
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray()
            ];
            
            $role->update(['name' => $request->name]);

            if ($request->has('permissions') && is_array($request->permissions)) {
                $role->syncPermissions($request->permissions);
            }

            $role->load('permissions');

            // Registrar actividad de actualización
            ActivityLoggerService::logUpdate('Role', $role->id, 'roles', $oldValues, [
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray()
            ]);

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
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                "message" => 422,
                "message_text" => "Datos de entrada inválidos",
                "errors" => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                "message" => 404,
                "message_text" => "El rol especificado no fue encontrado"
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                "message" => 500,
                "message_text" => "Error interno del servidor al actualizar el rol"
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $role = Role::findOrFail($id);

            // Verificar que no se esté intentando eliminar los roles de Director o Subdirector
            if (in_array($role->name, ['Director General', 'Subdirector General'])) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "No se pueden eliminar los roles administrativos del sistema"
                ], 403);
            }

            if ($role->users && $role->users->count() > 0) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "EL ROL SELECCIONADO NO SE PUEDE ELIMINAR PORQUE TIENE USUARIOS RELACIONADOS"
                ], 403);
            }

            // Log the deletion activity
            ActivityLoggerService::logDelete('Role', $role->id, 'roles', [
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions->pluck('name')->toArray()
            ]);

            $role->delete();

            return response()->json([
                "message" => 200,
                "message_text" => "Rol eliminado correctamente"
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                "message" => 404,
                "message_text" => "El rol especificado no fue encontrado"
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                "message" => 500,
                "message_text" => "Error interno del servidor al eliminar el rol"
            ], 500);
        }
    }
}
