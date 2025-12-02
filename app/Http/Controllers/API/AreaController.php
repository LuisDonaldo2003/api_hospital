<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\ActivityLoggerService;

class AreaController extends Controller
{
    /**
     * Listar todas las áreas
     */
    public function index()
    {
        try {
            $areas = DB::table('areas')
                ->orderBy('nombre', 'asc')
                ->get();

            ActivityLoggerService::logRead('Area', null, 'areas', ['total_records' => count($areas)]);

            return response()->json([
                'success' => true,
                'data' => $areas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener áreas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un área por ID
     */
    public function show($id)
    {
        try {
            $area = DB::table('areas')->where('id', $id)->first();

            if (!$area) {
                return response()->json([
                    'success' => false,
                    'message' => 'Área no encontrada'
                ], 404);
            }

            ActivityLoggerService::logRead('Area', $id, 'areas', ['nombre' => $area->nombre]);

            return response()->json([
                'success' => true,
                'data' => $area
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener área',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nueva área
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255|unique:areas,nombre',
                'descripcion' => 'nullable|string|max:500',
                'activo' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $id = DB::table('areas')->insertGetId([
                'nombre' => strtoupper($request->nombre),
                'descripcion' => $request->descripcion,
                'activo' => $request->activo ?? true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $area = DB::table('areas')->where('id', $id)->first();

            ActivityLoggerService::logCreate('Area', $id, 'areas', ['nombre' => $request->nombre]);

            return response()->json([
                'success' => true,
                'message' => 'Área creada correctamente',
                'data' => $area
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear área',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar área
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255|unique:areas,nombre,' . $id,
                'descripcion' => 'nullable|string|max:500',
                'activo' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::table('areas')
                ->where('id', $id)
                ->update([
                    'nombre' => strtoupper($request->nombre),
                    'descripcion' => $request->descripcion,
                    'activo' => $request->activo ?? true,
                    'updated_at' => now()
                ]);

            $area = DB::table('areas')->where('id', $id)->first();

            ActivityLoggerService::logUpdate('Area', $id, 'areas', [], ['nombre' => $request->nombre]);

            return response()->json([
                'success' => true,
                'message' => 'Área actualizada correctamente',
                'data' => $area
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar área',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar área
     */
    public function destroy($id)
    {
        try {
            // Verificar si hay teachings usando esta área
            $area = DB::table('areas')->where('id', $id)->first();
            
            if (!$area) {
                return response()->json([
                    'success' => false,
                    'message' => 'Área no encontrada'
                ], 404);
            }
            
            $count = DB::table('teachings')->where('area', $area->nombre)->count();
            
            if ($count > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede eliminar. Hay {$count} registro(s) usando esta área."
                ], 400);
            }

            ActivityLoggerService::logDelete('Area', $id, 'areas', []);

            DB::table('areas')->where('id', $id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Área eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar área',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activar/Desactivar área
     */
    public function toggleStatus($id)
    {
        try {
            $area = DB::table('areas')->where('id', $id)->first();
            
            if (!$area) {
                return response()->json([
                    'success' => false,
                    'message' => 'Área no encontrada'
                ], 404);
            }

            DB::table('areas')
                ->where('id', $id)
                ->update([
                    'activo' => !$area->activo,
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
