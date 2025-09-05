<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class PulseDirectorAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // Permitir logout sin verificar autenticación
        if ($request->is('pulse/logout')) {
            return $next($request);
        }
        
        // Verificar si existe una sesión de director válida
        if (!Session::has('pulse_director_authenticated')) {
            // Si es una solicitud para login/authenticate de Pulse, permitir continuar
            if ($request->is('pulse/login') || $request->is('pulse/authenticate')) {
                return $next($request);
            }
            
            // Si es una solicitud AJAX o API
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => 'No autorizado. Se requiere autenticación como Director General.'], 401);
            }
            
            // Redirigir al login de Pulse para solicitudes web
            return redirect('/pulse/login');
        }

        // Verificar que el usuario autenticado sigue siendo Director General O tiene permiso de acceso a Pulse
        $userId = Session::get('pulse_director_user_id');
        $user = User::find($userId);
        
        if (!$user || (!$user->hasRole('Director General') && !$user->can('access_pulse'))) {
            Session::forget('pulse_director_authenticated');
            Session::forget('pulse_director_user_id');
            Session::forget('pulse_director_name');
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => 'Sesión expirada o permisos insuficientes.'], 401);
            }
            
            return redirect('/pulse/login')->with('error', 'Tu sesión ha expirado o ya no tienes permisos.');
        }

        return $next($request);
    }
}
