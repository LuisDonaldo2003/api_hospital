<?php

namespace App\Http\Controllers\Admin\RoleFamily;

use App\Models\RoleFamily;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\ActivityLoggerService;

/**
 * Controlador para gestionar las familias de roles.
 * Permite CRUD completo de familias de roles.
 */
class RoleFamilyController extends Controller
{
    /**
     * Listar todas las familias de roles
     */
    public function index(Request $request)
    {
        $query = RoleFamily::with('roles');

        if ($request->filled('search')) {
            $query->where("name", "like", "%" . $request->search . "%");
        }

        $families = $query->orderBy("id", "desc")->get();

        // Log de acceso a listado de familias
        ActivityLoggerService::logRead('RoleFamily', null, 'role_families', [
            'search_term' => $request->search,
            'total_results' => $families->count()
        ]);

        return response()->json([
            "families" => $families,
        ]);
    }

    /**
     * Crear una nueva familia de roles
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:role_families,name',
            'description' => 'nullable|string',
        ]);

        $family = RoleFamily::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        // Registrar actividad de creación
        ActivityLoggerService::logCreate('RoleFamily', $family->id, 'role_families', [
            'name' => $family->name,
        ]);

        return response()->json([
            "message" => 200,
            "message_text" => "Familia de roles creada correctamente",
            "family" => $family
        ], 201);
    }

    /**
     * Mostrar una familia específica
     */
    public function show(string $id)
    {
        $family = RoleFamily::with('roles')->findOrFail($id);

        // Log de lectura
        ActivityLoggerService::logRead('RoleFamily', $family->id, 'role_families', [
            'name' => $family->name
        ]);

        return response()->json([
            "family" => $family
        ]);
    }

    /**
     * Actualizar una familia de roles
     */
    public function update(Request $request, string $id)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:role_families,name,' . $id,
                'description' => 'nullable|string',
            ]);

            $family = RoleFamily::findOrFail($id);
            
            // Guardar valores anteriores para el log
            $oldValues = [
                'name' => $family->name,
                'description' => $family->description,
            ];
            
            $family->update([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            // Registrar actividad de actualización
            ActivityLoggerService::logUpdate('RoleFamily', $family->id, 'role_families', $oldValues, [
                'name' => $family->name,
                'description' => $family->description,
            ]);

            return response()->json([
                "message" => 200,
                "message_text" => "Familia de roles actualizada correctamente",
                "family" => $family
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
                "message_text" => "La familia especificada no fue encontrada"
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                "message" => 500,
                "message_text" => "Error interno del servidor"
            ], 500);
        }
    }

    /**
     * Eliminar una familia de roles
     */
    public function destroy(string $id)
    {
        try {
            $family = RoleFamily::findOrFail($id);

            // Log de eliminación
            ActivityLoggerService::logDelete('RoleFamily', $family->id, 'role_families', [
                'name' => $family->name,
            ]);

            $family->delete();

            return response()->json([
                "message" => 200,
                "message_text" => "Familia de roles eliminada correctamente"
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                "message" => 404,
                "message_text" => "La familia especificada no fue encontrada"
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                "message" => 500,
                "message_text" => "Error interno del servidor"
            ], 500);
        }
    }

    /**
     * Asignar roles a una familia
     */
    public function assignRoles(Request $request, string $id)
    {
        try {
            $request->validate([
                'role_ids' => 'required|array',
                'role_ids.*' => 'exists:roles,id'
            ]);

            $family = RoleFamily::findOrFail($id);
            
            // Sincronizar roles (agregar nuevos, eliminar los que no están)
            $family->roles()->sync($request->role_ids);

            // Log
            ActivityLoggerService::logUpdate('RoleFamily', $family->id, 'role_families', [], [
                'assigned_roles' => $request->role_ids
            ]);

            return response()->json([
                "message" => 200,
                "message_text" => "Roles asignados correctamente",
                "family" => $family->load('roles')
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                "message" => 500,
                "message_text" => "Error al asignar roles"
            ], 500);
        }
    }
}
