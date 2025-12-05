<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Especialidad;
use App\Services\ActivityLoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EspecialidadController extends Controller
{
    /**
     * Listar todas las especialidades
     */
    public function index()
    {
        $especialidades = Especialidad::orderBy('nombre', 'asc')->get();

        ActivityLoggerService::logRead('Especialidad', null, 'especialidades', [
            'total_records' => $especialidades->count()
        ]);

        return response()->json([
            'success' => true,
            'data' => $especialidades
        ]);
    }

    /**
     * Obtener una especialidad específica
     */
    public function show($id)
    {
        $especialidad = Especialidad::find($id);
        
        if (!$especialidad) {
            return response()->json([
                'success' => false,
                'message' => 'Especialidad no encontrada'
            ], 404);
        }

        ActivityLoggerService::logRead('Especialidad', $especialidad->id, 'especialidades', [
            'nombre' => $especialidad->nombre
        ]);

        return response()->json([
            'success' => true,
            'data' => $especialidad
        ]);
    }

    /**
     * Crear nueva especialidad
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255|unique:especialidades,nombre',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $especialidad = Especialidad::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'activo' => $request->activo ?? true
        ]);

        ActivityLoggerService::logCreate('Especialidad', $especialidad->id, 'especialidades', [
            'nombre' => $especialidad->nombre
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Especialidad creada exitosamente',
            'data' => $especialidad
        ], 201);
    }

    /**
     * Actualizar especialidad
     */
    public function update(Request $request, $id)
    {
        $especialidad = Especialidad::find($id);
        
        if (!$especialidad) {
            return response()->json([
                'success' => false,
                'message' => 'Especialidad no encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255|unique:especialidades,nombre,' . $id,
            'descripcion' => 'nullable|string',
            'activo' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $oldData = $especialidad->toArray();

        $especialidad->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion ?? $especialidad->descripcion,
            'activo' => $request->activo ?? $especialidad->activo
        ]);

        ActivityLoggerService::logUpdate('Especialidad', $especialidad->id, 'especialidades', $oldData, $especialidad->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Especialidad actualizada exitosamente',
            'data' => $especialidad
        ]);
    }

    /**
     * Eliminar especialidad
     */
    public function destroy($id)
    {
        $especialidad = Especialidad::find($id);
        
        if (!$especialidad) {
            return response()->json([
                'success' => false,
                'message' => 'Especialidad no encontrada'
            ], 404);
        }

        // Verificar si hay doctores asociados
        if ($especialidad->doctores()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la especialidad porque tiene doctores asociados'
            ], 400);
        }

        $oldData = $especialidad->toArray();
        $especialidad->delete();

        ActivityLoggerService::logDelete('Especialidad', $id, 'especialidades', $oldData);

        return response()->json([
            'success' => true,
            'message' => 'Especialidad eliminada exitosamente'
        ]);
    }
}
