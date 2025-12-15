<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\GeneralMedical;
use App\Services\ActivityLoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GeneralMedicalController extends Controller
{
    /**
     * Listar todos los médicos generales (categorías)
     */
    public function index()
    {
        $generalMedicals = GeneralMedical::orderBy('nombre', 'asc')->get();

        ActivityLoggerService::logRead('GeneralMedical', null, 'general_medicals', [
            'total_records' => $generalMedicals->count()
        ]);

        return response()->json([
            'success' => true,
            'data' => $generalMedicals
        ]);
    }

    /**
     * Obtener un médico general (categoría) específico
     */
    public function show($id)
    {
        $generalMedical = GeneralMedical::find($id);
        
        if (!$generalMedical) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }

        ActivityLoggerService::logRead('GeneralMedical', $generalMedical->id, 'general_medicals', [
            'nombre' => $generalMedical->nombre
        ]);

        return response()->json([
            'success' => true,
            'data' => $generalMedical
        ]);
    }

    /**
     * Crear nueva categoría de médico general
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255|unique:general_medicals,nombre',
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

        $generalMedical = GeneralMedical::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'activo' => $request->activo ?? true
        ]);

        ActivityLoggerService::logCreate('GeneralMedical', $generalMedical->id, 'general_medicals', [
            'nombre' => $generalMedical->nombre
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Categoría creada exitosamente',
            'data' => $generalMedical
        ], 201);
    }

    /**
     * Actualizar categoría de médico general
     */
    public function update(Request $request, $id)
    {
        $generalMedical = GeneralMedical::find($id);
        
        if (!$generalMedical) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255|unique:general_medicals,nombre,' . $id,
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

        $oldData = $generalMedical->toArray();

        $generalMedical->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion ?? $generalMedical->descripcion,
            'activo' => $request->activo ?? $generalMedical->activo
        ]);

        ActivityLoggerService::logUpdate('GeneralMedical', $generalMedical->id, 'general_medicals', $oldData, $generalMedical->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Categoría actualizada exitosamente',
            'data' => $generalMedical
        ]);
    }

    /**
     * Eliminar categoría de médico general
     */
    public function destroy($id)
    {
        $generalMedical = GeneralMedical::find($id);
        
        if (!$generalMedical) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }

        $oldData = $generalMedical->toArray();
        $generalMedical->delete();

        ActivityLoggerService::logDelete('GeneralMedical', $id, 'general_medicals', $oldData);

        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada exitosamente'
        ]);
    }
}
