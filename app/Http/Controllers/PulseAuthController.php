<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class PulseAuthController extends Controller
{
    /**
     * Mostrar la página de login de Pulse
     */
    public function showLogin()
    {
        return view('pulse.login');
    }

    /**
     * Procesar el login de Pulse
     */
    public function authenticate(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Buscar el usuario por email
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
            ])->withInput();
        }

        // Verificar que tenga el rol de Director General
        if (!$user->hasRole('Director General')) {
            return back()->withErrors([
                'email' => 'No tienes permisos para acceder a Laravel Pulse. Solo el Director General puede acceder.',
            ])->withInput();
        }

        // Establecer la sesión de autenticación para Pulse
        Session::put('pulse_director_authenticated', true);
        Session::put('pulse_director_user_id', $user->id);
        Session::put('pulse_director_name', $user->name);

        return redirect('/monitoring_dashboard_laravel'); // Redirigir al dashboard de Pulse
    }

    /**
     * Cerrar sesión de Pulse
     */
    public function logout(Request $request)
    {
        // Limpiar todas las sesiones relacionadas con Pulse
        Session::forget('pulse_director_authenticated');
        Session::forget('pulse_director_user_id');
        Session::forget('pulse_director_name');
        
        // Forzar regeneración del ID de sesión
        $request->session()->regenerate();
        
        return redirect('/pulse/login')->with('message', 'Sesión cerrada correctamente.');
    }

    /**
     * Verificar el estado de autenticación (para AJAX)
     */
    public function checkAuth(Request $request)
    {
        if (!Session::has('pulse_director_authenticated')) {
            return response()->json(['authenticated' => false], 401);
        }

        $userId = Session::get('pulse_director_user_id');
        $user = User::find($userId);
        
        if (!$user || !$user->hasRole('Director General')) {
            Session::forget('pulse_director_authenticated');
            Session::forget('pulse_director_user_id');
            Session::forget('pulse_director_name');
            return response()->json(['authenticated' => false], 401);
        }

        return response()->json([
            'authenticated' => true,
            'user' => [
                'name' => $user->name,
                'email' => $user->email
            ]
        ]);
    }
}
