<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class UpdateUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Solo actualizar si el usuario está autenticado
        if (Auth::guard('api')->check()) {
            $user = Auth::guard('api')->user();
            // Actualizar timestamp de actividad con 90 segundos de TTL
            Cache::put('user-is-online-' . $user->id, now()->timestamp, 90);
        }

        return $response;
    }
}
