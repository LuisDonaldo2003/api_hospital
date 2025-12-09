<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LicenseValidator;
use App\Services\HardwareSignatureService;

class LicenseController extends Controller
{
    /**
     * Verifica el estado de la licencia
     */
    public function status()
    {
        $isValid = LicenseValidator::isValid();
        $license = LicenseValidator::getActiveLicense();

        return response()->json([
            'valid' => $isValid,
            'message' => $isValid 
                ? 'Licencia válida' 
                : 'No hay licencia activa en el sistema',
            'has_license' => $license !== null,
            'type' => $license?->type,
            'expires_at' => $license?->expires_at?->format('Y-m-d'),
            'days_remaining' => $license?->daysRemaining()
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
                'message' => 'No existe una licencia activa en el sistema',
                'requires_activation' => true
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

            // Activar la licencia usando el servicio
            $result = LicenseValidator::activateLicense(
                $licenseContent,
                auth()->check() ? auth()->id() : null,
                $request->ip()
            );

            if (!$result['success']) {
                $statusCode = 400;
                
                // Determinar código de estado según el tipo de error
                if (isset($result['error_code'])) {
                    $statusCode = match($result['error_code']) {
                        'HARDWARE_MISMATCH' => 403,
                        'ALREADY_ACTIVATED' => 409,
                        default => 400,
                    };
                }
                
                return response()->json([
                    'error' => 'Error al activar licencia',
                    'message' => $result['message'],
                    'error_code' => $result['error_code'] ?? 'UNKNOWN',
                    'details' => $result['current_hardware'] ?? $result['activated_on'] ?? null,
                ], $statusCode);
            }

            // Registrar en historial
            \App\Models\LicenseHistory::create([
                'user_id' => auth()->check() ? auth()->id() : null,
                'institution' => $result['license']['institution'] ?? 'N/A',
                'valid_until' => $result['license']['expires_at'] ?? 'PERMANENT',
                'uploaded_by' => auth()->check() ? auth()->user()->name : 'Activación Inicial',
                'ip_address' => $request->ip(),
                'filename' => $file->getClientOriginalName(),
            ]);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'license_info' => $result['license']
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

    /**
     * Obtiene información del hardware del servidor actual
     */
    public function hardwareInfo()
    {
        try {
            $hardwareInfo = HardwareSignatureService::getHardwareInfo();
            $serverIdentifier = HardwareSignatureService::getServerIdentifier();

            return response()->json([
                'hardware_info' => $hardwareInfo,
                'server_identifier' => $serverIdentifier,
                'signature' => $hardwareInfo['signature'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo obtener información del hardware',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene las activaciones de licencia
     */
    public function activations()
    {
        try {
            $activations = \App\Models\LicenseActivation::with('activatedBy')
                ->orderBy('activated_at', 'desc')
                ->get();

            return response()->json($activations);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo obtener el historial de activaciones',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
