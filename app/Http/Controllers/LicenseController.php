<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LicenseValidator;

class LicenseController extends Controller
{
    /**
     * Verifica el estado de la licencia
     */
    public function status()
    {
        $isValid = LicenseValidator::isValid();

        return response()->json([
            'valid' => $isValid,
            'message' => $isValid 
                ? 'Licencia válida' 
                : 'Licencia inválida o expirada'
        ]);
    }

    /**
     * Obtiene información detallada de la licencia
     */
    public function info()
    {
        $info = LicenseValidator::getLicenseInfo();

        if (!$info) {
            return response()->json([
                'error' => 'No se pudo obtener información de la licencia',
                'message' => 'No existe un archivo de licencia válido en el sistema'
            ], 404);
        }

        return response()->json($info);
    }

    /**
     * Verifica si una característica específica está habilitada
     */
    public function checkFeature(Request $request)
    {
        $feature = $request->input('feature');

        if (!$feature) {
            return response()->json([
                'error' => 'Parámetro feature requerido'
            ], 400);
        }

        $hasFeature = LicenseValidator::hasFeature($feature);

        return response()->json([
            'feature' => $feature,
            'enabled' => $hasFeature
        ]);
    }

    /**
     * Sube y activa una nueva licencia
     */
    public function upload(Request $request)
    {
        try {
            // Validar que se haya enviado un archivo
            $request->validate([
                'license_file' => 'required|file|max:10240', // Máximo 10MB
            ]);

            $file = $request->file('license_file');
            
            // Validar extensión
            $extension = $file->getClientOriginalExtension();
            if ($extension !== 'license' && $extension !== 'key') {
                return response()->json([
                    'error' => 'Formato de archivo inválido',
                    'message' => 'Solo se permiten archivos .license o .key'
                ], 400);
            }

            // Leer contenido del archivo
            $licenseContent = file_get_contents($file->getRealPath());

            // Guardar temporalmente para validar
            $tempPath = storage_path('app/license_temp.key');
            file_put_contents($tempPath, $licenseContent);

            // Validar la licencia antes de activarla
            // Temporalmente renombrar la actual si existe
            $currentPath = storage_path('app/license.key');
            $backupPath = storage_path('app/license_backup_' . time() . '.key');
            
            if (file_exists($currentPath)) {
                rename($currentPath, $backupPath);
            }

            // Mover la nueva licencia
            rename($tempPath, $currentPath);

            // Validar la nueva licencia
            if (!LicenseValidator::isValid()) {
                // Si no es válida, restaurar la anterior
                if (file_exists($backupPath)) {
                    rename($backupPath, $currentPath);
                }
                
                return response()->json([
                    'error' => 'Licencia inválida',
                    'message' => 'El archivo de licencia no es válido o ha sido alterado'
                ], 400);
            }

            // Licencia válida - obtener información
            $licenseInfo = LicenseValidator::getLicenseInfo();

            // Registrar en historial (si hay usuario autenticado)
            \App\Models\LicenseHistory::create([
                'user_id' => auth()->check() ? auth()->id() : null,
                'institution' => $licenseInfo['institution'] ?? 'N/A',
                'valid_until' => $licenseInfo['valid_until'] ?? null,
                'uploaded_by' => auth()->check() ? auth()->user()->name : 'Activación Inicial',
                'ip_address' => $request->ip(),
                'filename' => $file->getClientOriginalName(),
            ]);

            // Eliminar backup si todo salió bien
            if (file_exists($backupPath)) {
                unlink($backupPath);
            }

            return response()->json([
                'success' => true,
                'message' => 'Licencia activada correctamente',
                'license_info' => $licenseInfo
            ]);

        } catch (\Exception $e) {
            \Log::error('Error uploading license: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Error al procesar la licencia',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene el historial de licencias subidas
     */
    public function history()
    {
        $history = \App\Models\LicenseHistory::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($history);
    }
}
