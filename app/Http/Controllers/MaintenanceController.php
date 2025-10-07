<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;

class MaintenanceController extends Controller
{
    /**
     * Verifica si la aplicación está en modo de mantenimiento
     * 
     * @return JsonResponse
     */
    public function status(): JsonResponse
    {
        $isDown = App::isDownForMaintenance();
        
        $response = [
            'maintenance_mode' => $isDown,
            'status' => $isDown ? 'maintenance' : 'active',
            'message' => $isDown 
                ? 'Sistema en mantenimiento. Vuelva a intentar más tarde.' 
                : 'Sistema activo y funcionando.',
            'timestamp' => now()->toISOString()
        ];

        // Si está en mantenimiento, agregar información adicional
        if ($isDown) {
            try {
                $payload = json_decode(file_get_contents(storage_path('framework/maintenance.php')), true);
                if (isset($payload['data'])) {
                    $response['maintenance_info'] = [
                        'message' => $payload['data']['message'] ?? 'Mantenimiento programado',
                        'retry_after' => $payload['data']['retry'] ?? 3600,
                        'allowed_ips' => $payload['data']['allowed'] ?? []
                    ];
                }
            } catch (\Exception $e) {
                // Si hay error leyendo el archivo, usar valores por defecto
                $response['maintenance_info'] = [
                    'message' => 'Mantenimiento en progreso',
                    'retry_after' => 3600
                ];
            }
        }

        return response()->json($response, $isDown ? 503 : 200);
    }

    /**
     * Endpoint alternativo para Angular (más simple)
     * 
     * @return JsonResponse
     */
    public function check(): JsonResponse
    {
        return response()->json([
            'is_maintenance' => App::isDownForMaintenance()
        ], 200);
    }
}