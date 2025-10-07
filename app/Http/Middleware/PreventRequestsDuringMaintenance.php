<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventRequestsDuringMaintenance extends Middleware
{
    /**
     * The URIs that should be reachable while maintenance mode is enabled.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/maintenance/status',  // Endpoint para verificar estado de mantenimiento
        'api/maintenance/check',   // Endpoint alternativo para Angular
        'pulse',                   // Permite acceso a Laravel Pulse para monitoreo
        'pulse/*',                 // Todos los endpoints de Pulse
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        // Si la aplicación no está en mantenimiento, continuar normalmente
        if (!$this->app->isDownForMaintenance()) {
            return $next($request);
        }

        // Si es una petición API que debe estar permitida, continuar
        if ($this->inExceptArray($request)) {
            return $next($request);
        }

        // Si es una petición a archivos estáticos de Angular, permitir
        if ($this->isAngularAsset($request)) {
            return $next($request);
        }

        // Si es una petición de Angular (rutas del frontend), redirigir a Angular
        if ($this->isAngularRoute($request)) {
            $frontendUrl = config('app.frontend_url', 'http://localhost:4200');
            return redirect($frontendUrl . '/maintenance');
        }

        // Para todas las demás peticiones, usar el comportamiento por defecto de Laravel
        return parent::handle($request, $next);
    }

    /**
     * Determina si la petición es para un asset de Angular
     */
    private function isAngularAsset(Request $request): bool
    {
        $path = $request->getPathInfo();
        
        // Archivos estáticos comunes de Angular
        $staticExtensions = ['.js', '.css', '.ico', '.png', '.jpg', '.jpeg', '.svg', '.woff', '.woff2', '.ttf', '.eot'];
        
        foreach ($staticExtensions as $ext) {
            if (str_ends_with($path, $ext)) {
                return true;
            }
        }

        // Directorio de assets
        if (str_starts_with($path, '/assets/')) {
            return true;
        }

        return false;
    }

    /**
     * Determina si la petición es para una ruta de Angular
     */
    private function isAngularRoute(Request $request): bool
    {
        $path = $request->getPathInfo();
        
        // Rutas específicas de Angular que deben redirigir al frontend
        $angularRoutes = [
            '/maintenance',
            '/login',
            '/dashboard',
            '/admin',
            '/roles',
            '/staffs',
            '/archives',
            '/personal'
            // Agregar más rutas según sea necesario
        ];

        foreach ($angularRoutes as $route) {
            if (str_starts_with($path, $route)) {
                return true;
            }
        }

        return false;
    }
}
