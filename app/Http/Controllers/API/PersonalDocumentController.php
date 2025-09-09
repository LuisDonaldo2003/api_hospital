<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Personal;
use App\Models\PersonalDocument;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\ActivityLoggerService;

class PersonalDocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $personalId = $request->route('personal_id');
            
            if ($personalId) {
                // Documentos de una persona específica
                $personal = Personal::findOrFail($personalId);
                $documentos = $personal->documentos()->get();
            } else {
                // Todos los documentos
                $documentos = PersonalDocument::with('personal')->get();
            }

            // Log the list action
            ActivityLoggerService::logRead('PersonalDocument', null, 'medical-documents', [
                'personal_id' => $personalId,
                'total_results' => $documentos->count()
            ]);

            return response()->json([
                'success' => true,
                'data' => $documentos,
                'message' => 'Documentos obtenidos exitosamente'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Personal no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener documentos: ' . $e->getMessage()
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
                'personal_id' => 'required|exists:personals,id',
                'tipo_documento' => 'required|in:Acta de nacimiento,Comprobante de domicilio,CURP,INE,RFC,Título profesional',
                'archivo' => 'required|file|mimes:pdf|max:10240' // Max 10MB
            ], [
                'personal_id.required' => 'El ID del personal es requerido',
                'personal_id.exists' => 'El personal especificado no existe',
                'tipo_documento.required' => 'El tipo de documento es requerido',
                'tipo_documento.in' => 'Tipo de documento inválido',
                'archivo.required' => 'El archivo es requerido',
                'archivo.mimes' => 'El archivo debe ser un PDF',
                'archivo.max' => 'El archivo no puede ser mayor a 10MB'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $personal = Personal::findOrFail($request->personal_id);

            // Verificar si ya existe un documento de este tipo para esta persona
            $existeDocumento = PersonalDocument::where('personal_id', $request->personal_id)
                                             ->where('tipo_documento', $request->tipo_documento)
                                             ->first();

            if ($existeDocumento) {
                // Eliminar el archivo anterior
                if (Storage::exists($existeDocumento->ruta_archivo)) {
                    Storage::delete($existeDocumento->ruta_archivo);
                }
                $existeDocumento->delete();
            }

            $archivo = $request->file('archivo');
            $nombreArchivo = $this->generateFileName($personal, $request->tipo_documento, $archivo);
            $rutaArchivo = $archivo->storeAs('documentos/personal/' . $personal->id, $nombreArchivo, 'public');

            $documento = PersonalDocument::create([
                'personal_id' => $request->personal_id,
                'tipo_documento' => $request->tipo_documento,
                'nombre_archivo' => $nombreArchivo,
                'ruta_archivo' => $rutaArchivo,
                'tipo_mime' => $archivo->getMimeType(),
                'tamaño_archivo' => $archivo->getSize(),
                'fecha_subida' => now()
            ]);

            return response()->json([
                'success' => true,
                'data' => $documento->load('personal'),
                'message' => 'Documento subido exitosamente'
            ], 201);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Personal no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir documento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $documento = PersonalDocument::with('personal')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $documento,
                'message' => 'Documento obtenido exitosamente'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Documento no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener documento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $documento = PersonalDocument::findOrFail($id);
            $documento->delete(); // El archivo se elimina automáticamente por el evento boot()

            return response()->json([
                'success' => true,
                'message' => 'Documento eliminado exitosamente'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Documento no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar documento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar un documento
     */
    public function download(string $id)
    {
        try {
            $documento = PersonalDocument::findOrFail($id);
            
            if (!Storage::exists($documento->ruta_archivo)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado en el sistema'
                ], 404);
            }

            return Storage::download($documento->ruta_archivo, $documento->nombre_archivo);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Documento no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar documento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tipos de documentos requeridos
     */
    public function tiposDocumentos(): JsonResponse
    {
        $tipos = [
            'Acta de nacimiento',
            'Comprobante de domicilio',
            'CURP',
            'INE',
            'RFC',
            'Título profesional'
        ];

        return response()->json([
            'success' => true,
            'data' => $tipos,
            'message' => 'Tipos de documentos obtenidos exitosamente'
        ]);
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
     * Verificar el estado de documentos de una persona
     */
    public function estadoDocumentos(string $personalId): JsonResponse
    {
        try {
            $personal = Personal::findOrFail($personalId);
            $documentosSubidos = $personal->documentos()->pluck('tipo_documento')->toArray();
            
            $tiposRequeridos = [
                'Acta de nacimiento',
                'Comprobante de domicilio', 
                'CURP',
                'INE',
                'RFC',
                'Título profesional'
            ];

            $estado = [];
            foreach ($tiposRequeridos as $tipo) {
                $estado[$tipo] = in_array($tipo, $documentosSubidos);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'personal' => $personal,
                    'documentos_estado' => $estado,
                    'completo' => $personal->documentos_completos,
                    'total_requeridos' => count($tiposRequeridos),
                    'total_subidos' => count($documentosSubidos)
                ],
                'message' => 'Estado de documentos obtenido exitosamente'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Personal no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estado de documentos: ' . $e->getMessage()
            ], 500);
        }
    }
}
