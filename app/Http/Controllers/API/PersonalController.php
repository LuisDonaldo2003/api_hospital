<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Personal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\ActivityLoggerService;

class PersonalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // No cargar relaciones innecesarias para el listado
            $query = Personal::query();

            // Filtrar por tipo si se especifica
            if ($request->has('tipo') && $request->tipo) {
                $query->tipo($request->tipo);
            }

            // Filtrar por estado activo
            if ($request->has('activo')) {
                $query->where('activo', $request->boolean('activo'));
            } else {
                $query->activo(); // Por defecto solo activos
            }

            // Búsqueda por texto (nombre, apellidos, RFC, número de checador)
            if ($request->has('search') && $request->search) {
                $query->busqueda($request->search);
            }

            // Obtener total antes de aplicar limit/offset
            $totalData = $query->count();

            // Aplicar paginación
            if ($request->has('skip')) {
                $query->skip($request->integer('skip'));
            }
            
            if ($request->has('limit')) {
                $query->take($request->integer('limit'));
            }

            // Cargar datos con conteo de documentos en una sola consulta
            // Ahora obtenemos el personal y contamos sus documentos
            $personal = $query->withCount('documentos')
                             ->orderBy('apellidos')
                             ->orderBy('nombre')
                             ->get();

            // Agregar el campo documentos_completos basado en el conteo (ahora son 7 tipos de documentos)
            $personal->each(function ($item) {
                $item->documentos_completos = $item->documentos_count >= 7; 
            });

            // Log the list action
            ActivityLoggerService::logRead('Personal', null, 'medical-personal', [
                'search_term' => $request->search,
                'tipo_filter' => $request->tipo,
                'total_results' => $totalData
            ]);

            return response()->json([
                'success' => true,
                'data' => $personal,
                'total' => $totalData,
                'message' => 'Personal obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el personal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|min:2|max:255',
                'apellidos' => 'required|string|min:2|max:255',
                'tipo' => 'required|in:Clínico,No Clínico',
                'rfc' => 'required|string|max:13|regex:/^[A-Z0-9]+$/|unique:personals,rfc',
                'numero_checador' => 'required|string|regex:/^[0-9]{1,4}$/|unique:personals,numero_checador'
            ], [
                'nombre.required' => 'El nombre es requerido',
                'nombre.min' => 'El nombre debe tener al menos 2 caracteres',
                'apellidos.required' => 'Los apellidos son requeridos',
                'apellidos.min' => 'Los apellidos deben tener al menos 2 caracteres',
                'tipo.required' => 'El tipo de personal es requerido',
                'tipo.in' => 'El tipo debe ser Clínico o No Clínico',
                'rfc.required' => 'El RFC es requerido',
                'rfc.size' => 'El RFC debe tener exactamente 13 caracteres',
                'rfc.regex' => 'El formato del RFC no es válido (solo mayúsculas y números)',
                'rfc.unique' => 'Este RFC ya está registrado por otro empleado',
                'numero_checador.required' => 'El número de checador es requerido',
                'numero_checador.regex' => 'El número de checador debe tener entre 1 y 4 dígitos',
                'numero_checador.unique' => 'Este número de checador ya está asignado a otro empleado'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $personal = Personal::create([
                'nombre' => $request->nombre,
                'apellidos' => $request->apellidos,
                'tipo' => $request->tipo,
                'rfc' => strtoupper($request->rfc),
                'numero_checador' => $request->numero_checador,
                'fecha_ingreso' => now()->toDateString()
            ]);

            // Log the creation activity
            ActivityLoggerService::logCreate('Personal', $personal->id, 'medical-personal', [
                'nombre' => $personal->nombre,
                'apellidos' => $personal->apellidos,
                'tipo' => $personal->tipo,
                'fecha_ingreso' => $personal->fecha_ingreso
            ]);

            return response()->json([
                'success' => true,
                'data' => $personal,
                'message' => 'Personal creado exitosamente. Ahora puede subir los documentos.'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el personal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $personal = Personal::with('documentos')->findOrFail($id);
            // Recalcular completitud real basado en tipos únicos
            $tiposUnicos = $personal->documentos->pluck('tipo_documento')->unique()->count();
            $personal->documentos_completos = $tiposUnicos >= 7;

            // Log the read activity
            ActivityLoggerService::logRead('Personal', $personal->id, 'medical-personal', [
                'nombre' => $personal->nombre,
                'apellidos' => $personal->apellidos,
                'tipo' => $personal->tipo,
                'documentos_count' => $personal->documentos->count()
            ]);

            return response()->json([
                'success' => true,
                'data' => $personal,
                'message' => 'Personal obtenido exitosamente'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Personal no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el personal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $personal = Personal::findOrFail($id);

            // Store old values for logging
            $oldValues = [
                'nombre' => $personal->nombre,
                'apellidos' => $personal->apellidos,
                'tipo' => $personal->tipo,
                'rfc' => $personal->rfc,
                'numero_checador' => $personal->numero_checador,
                'activo' => $personal->activo
            ];

            $validator = Validator::make($request->all(), [
                'nombre' => 'sometimes|required|string|min:2|max:255',
                'apellidos' => 'sometimes|required|string|min:2|max:255',
                'tipo' => 'sometimes|required|in:Clínico,No Clínico',
                'rfc' => 'sometimes|required|string|max:13|regex:/^[A-Z0-9]+$/|unique:personals,rfc,' . $id,
                'numero_checador' => 'sometimes|required|string|regex:/^[0-9]{1,4}$/|unique:personals,numero_checador,' . $id,
                'activo' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Preparar datos para actualización
            $updateData = $request->only(['nombre', 'apellidos', 'tipo', 'numero_checador', 'activo']);
            if ($request->has('rfc')) {
                $updateData['rfc'] = strtoupper($request->rfc);
            }

            $personal->update($updateData);

            // Store new values for logging
            $newValues = [
                'nombre' => $personal->nombre,
                'apellidos' => $personal->apellidos,
                'tipo' => $personal->tipo,
                'rfc' => $personal->rfc,
                'numero_checador' => $personal->numero_checador,
                'activo' => $personal->activo
            ];

            // Log the update activity
            ActivityLoggerService::logUpdate('Personal', $personal->id, 'medical-personal', $oldValues, $newValues);

            return response()->json([
                'success' => true,
                'data' => $personal->fresh(),
                'message' => 'Personal actualizado exitosamente'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Personal no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el personal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $personal = Personal::with('documentos')->findOrFail($id);
            
            DB::beginTransaction();
            
            // Contar documentos antes de eliminar
            $totalDocumentos = $personal->documentos->count();
            
            // Log the deletion activity before deleting
            ActivityLoggerService::logDelete('Personal', $personal->id, 'medical-personal', [
                'nombre' => $personal->nombre,
                'apellidos' => $personal->apellidos,
                'tipo' => $personal->tipo,
                'documentos_count' => $totalDocumentos,
                'fecha_ingreso' => $personal->fecha_ingreso
            ]);
            
            // La eliminación en cascada se maneja automáticamente:
            // 1. La FK constraint elimina los registros de personal_documents
            // 2. El evento boot() en PersonalDocument elimina cada archivo del storage
            $personal->delete();
            
            // Eliminar la carpeta completa del personal si existe
            $carpetaPersonal = 'documentos/personal/' . $id;
            if (Storage::disk('public')->exists($carpetaPersonal)) {
                Storage::disk('public')->deleteDirectory($carpetaPersonal);
            }
            
            DB::commit();
            
            $mensaje = "Personal eliminado exitosamente";
            if ($totalDocumentos > 0) {
                $mensaje .= " junto con {$totalDocumentos} documento(s) y sus archivos asociados";
            }

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'data' => [
                    'documentos_eliminados' => $totalDocumentos
                ]
            ]);

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Personal no encontrado'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el personal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas del personal
     */
    public function estadisticas(): JsonResponse
    {
        try {
            // Documentos completos es complejo calcular en SQL puro si depende de "Tipos únicos >= 7"
            // Hacemos una aproximación o iteramos
            // Para estadisticas generales, podemos hacer un query group by
            
            $total = Personal::activo()->count();
            $clinico = Personal::activo()->tipo('Clínico')->count();
            $noClinico = Personal::activo()->tipo('No Clínico')->count();

            // Calculo de completos (mas costoso, pero necesario)
            // Filtramos en memoria los que tienen >= 7 tipos distintos
            // Ojo: esto puede ser lento si hay miles. Para MVP está bien.
            $completos = Personal::activo()->with('documentos')->get()->filter(function ($personal) {
                 return $personal->documentos->pluck('tipo_documento')->unique()->count() >= 7;
            })->count();

            $stats = [
                'total' => $total,
                'clinico' => $clinico,
                'no_clinico' => $noClinico,
                'documentos_completos' => $completos
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Estadísticas obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }
}
