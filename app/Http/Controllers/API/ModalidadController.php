<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ModalidadController extends Controller
{
    /**
     * Listar todas las modalidades
     */
    public function index()
    {
        try {
            $modalidades = DB::table('modalidades')
                ->orderBy('nombre', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $modalidades
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener modalidades',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener una modalidad por ID
     */
    public function show($id)
    {
        try {
            $modalidad = DB::table('modalidades')->where('id', $id)->first();

            if (!$modalidad) {
                return response()->json([
                    'success' => false,
                    'message' => 'Modalidad no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $modalidad
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener modalidad',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nueva modalidad
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'codigo' => 'required|string|max:50|unique:modalidades,codigo',
                'nombre' => 'required|string|max:255|unique:modalidades,nombre',
                'activo' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $id = DB::table('modalidades')->insertGetId([
                'codigo' => $request->codigo,
                'nombre' => $request->nombre,
                'activo' => $request->activo ?? true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $modalidad = DB::table('modalidades')->where('id', $id)->first();

            return response()->json([
                'success' => true,
                'message' => 'Modalidad creada correctamente',
                'data' => $modalidad
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear modalidad',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar modalidad
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'codigo' => 'required|string|max:50|unique:modalidades,codigo,' . $id,
                'nombre' => 'required|string|max:255|unique:modalidades,nombre,' . $id,
                'activo' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::table('modalidades')
                ->where('id', $id)
                ->update([
                    'codigo' => $request->codigo,
                    'nombre' => $request->nombre,
                    'activo' => $request->activo ?? true,
                    'updated_at' => now()
                ]);

            $modalidad = DB::table('modalidades')->where('id', $id)->first();

            return response()->json([
                'success' => true,
                'message' => 'Modalidad actualizada correctamente',
                'data' => $modalidad
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar modalidad',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar modalidad
     */
    public function destroy($id)
    {
        try {
            // Verificar si hay teachings usando esta modalidad
            $count = DB::table('teachings')->where('modalidad_id', $id)->count();
            
            if ($count > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede eliminar. Hay {$count} registro(s) usando esta modalidad."
                ], 400);
            }

            DB::table('modalidades')->where('id', $id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Modalidad eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar modalidad',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activar/Desactivar modalidad
     */
    public function toggleStatus($id)
    {
        try {
            $modalidad = DB::table('modalidades')->where('id', $id)->first();
            
            if (!$modalidad) {
                return response()->json([
                    'success' => false,
                    'message' => 'Modalidad no encontrada'
                ], 404);
            }

            DB::table('modalidades')
                ->where('id', $id)
                ->update([
                    'activo' => !$modalidad->activo,
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
