<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\LicenseValidator;
use Symfony\Component\HttpFoundation\Response;

class CheckLicense
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Rutas excluidas de la verificación de licencia
        $excludedRoutes = [
            'api/license/status',
            'api/license/info',
            'api/license/upload',
            'api/license/history',
        ];

        // Verificar si la ruta actual está excluida
        foreach ($excludedRoutes as $route) {
            if ($request->is($route)) {
                return $next($request);
            }
        }

        // Validar licencia
        if (!LicenseValidator::isValid()) {
            return response()->json([
                'error' => 'Licencia inválida o expirada',
                'message' => 'La licencia de uso del sistema no es válida o ha expirado. Por favor, contacte al proveedor para renovar su licencia.',
                'code' => 'LICENSE_INVALID'
            ], 403);
        }

        return $next($request);
    }
}
