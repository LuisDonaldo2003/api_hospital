<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowMaintenanceRoutes
{
    /**
     * Rutas que deben permanecer accesibles durante el mantenimiento
     */
    protected $allowedRoutes = [
        'api/maintenance/status',
        'api/maintenance/check',
        'api/auth/login',
        'api/auth/refresh',
        'maintenance',
        'pulse',
        'pulse/*'
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si la aplicación no está en mantenimiento, permitir todas las rutas
        if (!app()->isDownForMaintenance()) {
            return $next($request);
        }

        $currentPath = trim($request->getPathInfo(), '/');
        
        // Verificar si la ruta actual está en la lista de rutas permitidas
        foreach ($this->allowedRoutes as $allowedRoute) {
            if ($this->matchesRoute($currentPath, $allowedRoute)) {
                return $next($request);
            }
        }

        // Verificar si hay un token secreto válido
        if ($this->hasValidSecret($request)) {
            return $next($request);
        }

        // Para todas las demás rutas durante mantenimiento, 
        // retornar respuesta 503 con información del mantenimiento
        return $this->maintenanceResponse();
    }

    /**
     * Verifica si la ruta actual coincide con una ruta permitida
     */
    private function matchesRoute(string $currentPath, string $allowedRoute): bool
    {
        // Eliminar barras al inicio y final
        $currentPath = trim($currentPath, '/');
        $allowedRoute = trim($allowedRoute, '/');

        // Si la ruta permitida termina con *, hacer coincidencia parcial
        if (str_ends_with($allowedRoute, '*')) {
            $prefix = rtrim($allowedRoute, '*');
            return str_starts_with($currentPath, $prefix);
        }

        // Coincidencia exacta
        return $currentPath === $allowedRoute;
    }

    /**
     * Verifica si la request tiene un token secreto válido
     */
    private function hasValidSecret(Request $request): bool
    {
        try {
            $maintenanceFile = storage_path('framework/maintenance.php');
            if (!file_exists($maintenanceFile)) {
                return false;
            }

            $payload = include $maintenanceFile;
            $secret = $payload['data']['secret'] ?? null;

            if (!$secret) {
                return false;
            }

            // Verificar en query parameters o headers
            $requestSecret = $request->query('secret') ?? $request->header('X-Maintenance-Secret');
            
            return $requestSecret === $secret;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Retorna una respuesta de mantenimiento
     */
    private function maintenanceResponse(): Response
    {
        $data = [
            'maintenance_mode' => true,
            'status' => 'maintenance',
            'message' => 'Sistema en mantenimiento. Intente más tarde.',
            'timestamp' => now()->toISOString()
        ];

        // Intentar obtener información adicional del mantenimiento
        try {
            $maintenanceFile = storage_path('framework/maintenance.php');
            if (file_exists($maintenanceFile)) {
                $payload = include $maintenanceFile;
                if (isset($payload['data'])) {
                    $data['maintenance_info'] = [
                        'message' => $payload['data']['message'] ?? 'Mantenimiento programado',
                        'retry_after' => $payload['data']['retry'] ?? 3600
                    ];
                }
            }
        } catch (\Exception $e) {
            // Si hay error, usar datos por defecto
        }

        return response()->json($data, 503);
    }
}
