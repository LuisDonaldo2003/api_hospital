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

            // Búsqueda por texto
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('apellidos', 'like', "%{$search}%");
                });
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
            $personal = $query->withCount('documentos')
                             ->orderBy('apellidos')
                             ->orderBy('nombre')
                             ->get();

            // Agregar el campo documentos_completos basado en el conteo
            $personal->each(function ($item) {
                $item->documentos_completos = $item->documentos_count >= 6;
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
                'tipo' => 'required|in:Clínico,No Clínico'
            ], [
                'nombre.required' => 'El nombre es requerido',
                'nombre.min' => 'El nombre debe tener al menos 2 caracteres',
                'apellidos.required' => 'Los apellidos son requeridos',
                'apellidos.min' => 'Los apellidos deben tener al menos 2 caracteres',
                'tipo.required' => 'El tipo de personal es requerido',
                'tipo.in' => 'El tipo debe ser Clínico o No Clínico'
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
                'message' => 'Personal creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el personal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource with documents in storage.
     */
    public function storeWithDocuments(Request $request): JsonResponse
    {
        try {
            // Validación de datos básicos
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|min:2|max:255',
                'apellidos' => 'required|string|min:2|max:255',
                'tipo' => 'required|in:Clínico,No Clínico',
                'documentos' => 'nullable|array|max:6',
                'documentos.*' => 'required|file|mimes:pdf|max:10240'
            ], [
                'nombre.required' => 'El nombre es requerido',
                'nombre.min' => 'El nombre debe tener al menos 2 caracteres',
                'apellidos.required' => 'Los apellidos son requeridos',
                'apellidos.min' => 'Los apellidos deben tener al menos 2 caracteres',
                'tipo.required' => 'El tipo de personal es requerido',
                'tipo.in' => 'El tipo debe ser Clínico o No Clínico',
                'documentos.max' => 'No puede subir más de 6 documentos',
                'documentos.*.mimes' => 'Todos los documentos deben ser archivos PDF',
                'documentos.*.max' => 'Cada documento no puede ser mayor a 10MB'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Crear el personal
            $personal = Personal::create([
                'nombre' => $request->nombre,
                'apellidos' => $request->apellidos,
                'tipo' => $request->tipo,
                'fecha_ingreso' => now()->toDateString()
            ]);

            // Subir y guardar cada documento (si hay)
            $documentosGuardados = [];
            if ($request->hasFile('documentos')) {
                foreach ($request->file('documentos') as $tipoDocumento => $archivo) {
                    $nombreArchivo = $this->generateFileName($personal, $tipoDocumento, $archivo);
                    $rutaArchivo = $archivo->storeAs('documentos/personal/' . $personal->id, $nombreArchivo, 'public');

                    $documento = $personal->documentos()->create([
                        'tipo_documento' => $tipoDocumento,
                        'nombre_archivo' => $nombreArchivo,
                        'ruta_archivo' => $rutaArchivo,
                        'tipo_mime' => $archivo->getMimeType(),
                        'tamaño_archivo' => $archivo->getSize(),
                        'fecha_subida' => now()
                    ]);

                    $documentosGuardados[] = $documento;
                }
            }

            // Cargar el personal con sus documentos
            $personal->load('documentos');

            // Mensaje personalizado según documentos subidos
            $cantidadDocumentos = count($documentosGuardados);
            if ($cantidadDocumentos === 6) {
                $mensaje = 'Personal y todos los documentos creados exitosamente';
            } elseif ($cantidadDocumentos > 0) {
                $mensaje = "Personal creado exitosamente con {$cantidadDocumentos}/6 documentos. Será marcado como documentos incompletos.";
            } else {
                $mensaje = 'Personal creado exitosamente sin documentos. Será marcado como documentos incompletos.';
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'personal' => $personal,
                    'documentos' => $documentosGuardados
                ],
                'message' => $mensaje
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el personal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar nombre único para el archivo
     */
    private function generateFileName(Personal $personal, string $tipoDocumento, $archivo): string
    {
        $extension = $archivo->getClientOriginalExtension();
        $nombreLimpio = preg_replace('/[^A-Za-z0-9\-]/', '_', $personal->nombre . '_' . $personal->apellidos);
        $tipoLimpio = preg_replace('/[^A-Za-z0-9\-]/', '_', $tipoDocumento);
        
        return $nombreLimpio . '_' . $tipoLimpio . '_' . time() . '.' . $extension;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $personal = Personal::with('documentos')->findOrFail($id);
            $personal->documentos_completos = $personal->documentos_completos;

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
                'activo' => $personal->activo
            ];

            $validator = Validator::make($request->all(), [
                'nombre' => 'sometimes|required|string|min:2|max:255',
                'apellidos' => 'sometimes|required|string|min:2|max:255',
                'tipo' => 'sometimes|required|in:Clínico,No Clínico',
                'activo' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $personal->update($request->all());

            // Store new values for logging
            $newValues = [
                'nombre' => $personal->nombre,
                'apellidos' => $personal->apellidos,
                'tipo' => $personal->tipo,
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
            $stats = [
                'total' => Personal::activo()->count(),
                'clinico' => Personal::activo()->tipo('Clínico')->count(),
                'no_clinico' => Personal::activo()->tipo('No Clínico')->count(),
                'documentos_completos' => Personal::activo()->get()->filter(function ($personal) {
                    return $personal->documentos_completos;
                })->count()
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
