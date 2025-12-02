<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\ActivityLoggerService;

class ParticipacionController extends Controller
{
    /**
     * Listar todas las participaciones
     */
    public function index()
    {
        try {
            $participaciones = DB::table('participaciones')
                ->orderBy('nombre', 'asc')
                ->get();

            ActivityLoggerService::logRead('Stakeholding', null, 'participaciones', ['total_records' => count($participaciones)]);

            return response()->json([
                'success' => true,
                'data' => $participaciones
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener participaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener una participación por ID
     */
    public function show($id)
    {
        try {
            $participacion = DB::table('participaciones')->where('id', $id)->first();

            if (!$participacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Participación no encontrada'
                ], 404);
            }

            ActivityLoggerService::logRead('Stakeholding', $id, 'participaciones', ['nombre' => $participacion->nombre]);

            return response()->json([
                'success' => true,
                'data' => $participacion
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener participación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nueva participación
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:100|unique:participaciones,nombre',
                'activo' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $id = DB::table('participaciones')->insertGetId([
                'nombre' => strtoupper($request->nombre),
                'activo' => $request->activo ?? true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $participacion = DB::table('participaciones')->where('id', $id)->first();

            ActivityLoggerService::logCreate('Stakeholding', $id, 'participaciones', ['nombre' => $request->nombre]);

            return response()->json([
                'success' => true,
                'message' => 'Participación creada correctamente',
                'data' => $participacion
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear participación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar participación
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:100|unique:participaciones,nombre,' . $id,
                'activo' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::table('participaciones')
                ->where('id', $id)
                ->update([
                    'nombre' => strtoupper($request->nombre),
                    'activo' => $request->activo ?? true,
                    'updated_at' => now()
                ]);

            $participacion = DB::table('participaciones')->where('id', $id)->first();

            ActivityLoggerService::logUpdate('Stakeholding', $id, 'participaciones', [], ['nombre' => $request->nombre]);

            return response()->json([
                'success' => true,
                'message' => 'Participación actualizada correctamente',
                'data' => $participacion
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar participación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar participación
     */
    public function destroy($id)
    {
        try {
            // Verificar si hay teachings usando esta participación
            $count = DB::table('teachings')->where('participacion_id', $id)->count();
            
            if ($count > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede eliminar. Hay {$count} registro(s) usando esta participación."
                ], 400);
            }

            ActivityLoggerService::logDelete('Stakeholding', $id, 'participaciones', []);

            DB::table('participaciones')->where('id', $id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Participación eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar participación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activar/Desactivar participación
     */
    public function toggleStatus($id)
    {
        try {
            $participacion = DB::table('participaciones')->where('id', $id)->first();
            
            if (!$participacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Participación no encontrada'
                ], 404);
            }

            DB::table('participaciones')
                ->where('id', $id)
                ->update([
                    'activo' => !$participacion->activo,
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
