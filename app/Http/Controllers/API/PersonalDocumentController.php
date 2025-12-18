<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Personal;
use App\Models\PersonalDocument;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Services\ActivityLoggerService; // Assuming this service exists given usage in PersonalController

class PersonalDocumentController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validación básica
            $request->validate([
                'personal_id' => 'required|exists:personals,id',
                'tipo_documento' => 'required|string',
                'archivo' => 'required|file|mimes:pdf|max:500' // Max 500KB
            ]);

            $personalId = $request->personal_id;
            $tipoDocumento = $request->tipo_documento;
            $file = $request->file('archivo');

            // Lógica de reemplazo:
            // Si NO es "Constancias de cursos", eliminamos el anterior de ese tipo si existe.
            if ($tipoDocumento !== 'Constancias de cursos') {
                $existingDoc = PersonalDocument::where('personal_id', $personalId)
                                               ->where('tipo_documento', $tipoDocumento)
                                               ->first();
                if ($existingDoc) {
                    // Eliminar archivo físico
                    if (Storage::disk('public')->exists($existingDoc->ruta_archivo)) {
                        Storage::disk('public')->delete($existingDoc->ruta_archivo);
                    }
                    // Eliminar registro
                    $existingDoc->delete();
                }
            }

            // Generar nombre y ruta
            // Limpiamos el nombre original de caracteres extraños
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $originalName);
            $fileName = time() . '_' . $cleanName . '.pdf';
            
            // Estructura: documentos/personal/{id}/{tipo}/filename
            // Normalizar tipo para carpeta
            $folderType = \Str::slug($tipoDocumento);
            $path = "documentos/personal/{$personalId}/{$folderType}";
            
            // Guardar archivo
            $filePath = $file->storeAs($path, $fileName, 'public');

            // Crear registro
            $document = PersonalDocument::create([
                'personal_id' => $personalId,
                'tipo_documento' => $tipoDocumento,
                'nombre_archivo' => $file->getClientOriginalName(), // Guardamos nombre original para display
                'ruta_archivo' => $filePath,
                'tipo_mime' => $file->getClientMimeType(),
                'tamaño_archivo' => $file->getSize(), // El modelo tiene 'tamaño_archivo', lo mantenemos en DB pero exponemos diferente
                'fecha_subida' => now()
            ]);

            // Transformar respuesta para evitar ñ
            $responseDoc = $document->toArray();
            $responseDoc['file_size'] = $document->tamaño_archivo; // Alias seguro
            // unset($responseDoc['tamaño_archivo']); // Opcional, pero mejor dejarlo por si acaso

            return response()->json([
                'success' => true,
                'data' => $responseDoc,
                'message' => 'Documento subido correctamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir documento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of documents for a specific person.
     */
    public function index($personalId): JsonResponse
    {
        try {
            $documents = PersonalDocument::where('personal_id', $personalId)->get();

            // Transformar colección para alias seguro
            $documents = $documents->map(function ($doc) {
                $d = $doc->toArray();
                $d['file_size'] = $doc->tamaño_archivo;
                return $d;
            });

            return response()->json([
                'success' => true,
                'data' => $documents,
                'message' => 'Documentos obtenidos correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener documentos'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $document = PersonalDocument::findOrFail($id);
            
            // Eliminar archivo físico
            if (Storage::disk('public')->exists($document->ruta_archivo)) {
                Storage::disk('public')->delete($document->ruta_archivo);
            }
            
            $document->delete();

            return response()->json([
                'success' => true,
                'message' => 'Documento eliminado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar documento'
            ], 500);
        }
    }

    /**
     * Download the specified resource.
     */
    public function download($id)
    {
        try {
            $document = PersonalDocument::findOrFail($id);
            
            if (Storage::disk('public')->exists($document->ruta_archivo)) {
                return Storage::disk('public')->download($document->ruta_archivo, $document->nombre_archivo);
            }
            
            return response()->json(['message' => 'Archivo no encontrado en el servidor'], 404);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al descargar archivo'], 500);
        }
    }

    /**
     * Obtener tipos de documentos requeridos (Helper endpoint)
     */
    public function getTipos(): JsonResponse
    {
        $tipos = [
            'Acta de nacimiento',
            'Comprobante de domicilio',
            'CURP',
            'INE',
            'Título profesional',
            'Constancias de cursos',
            'Cédula profesional'
        ];
        
        return response()->json([
            'success' => true,
            'data' => $tipos
        ]);
    }
}
