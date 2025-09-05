<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PulseAccessController extends Controller
{
    /**
     * Listar usuarios con sus permisos de acceso a Pulse
     */
    public function index(Request $request)
    {
        $query = User::with(['roles', 'permissions'])
            ->whereHas('roles', function($q) {
                $q->whereNotIn('name', ['Director General']); // Excluir Director General
            });

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where("name", "like", "%{$search}%")
                  ->orWhere("surname", "like", "%{$search}%")
                  ->orWhere("email", "like", "%{$search}%");
            });
        }

        $users = $query->orderBy("name", "asc")->get();

        $usersWithPulseAccess = $users->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'surname' => $user->surname,
                'email' => $user->email,
                'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
                'roles' => $user->roles->pluck('name'),
                'has_pulse_access' => $user->can('access_pulse'),
                'departament' => $user->departament ? $user->departament->name : null,
                'profile' => $user->profile ? $user->profile->name : null,
            ];
        });

        return response()->json([
            'users' => $usersWithPulseAccess,
            'total' => $users->count()
        ]);
    }

    /**
     * Asignar o quitar permiso de acceso a Pulse
     */
    public function togglePulseAccess(Request $request, $userId)
    {
        $request->validate([
            'grant_access' => 'required|boolean'
        ]);

        $user = User::findOrFail($userId);
        
        // Verificar que no sea el Director General
        if ($user->hasRole('Director General')) {
            return response()->json([
                'message' => 'No se puede modificar el acceso del Director General',
                'message_text' => 'El Director General siempre tiene acceso completo a Pulse'
            ], 403);
        }

        $pulsePermission = Permission::where('name', 'access_pulse')->first();
        
        if ($request->grant_access) {
            // Otorgar permiso
            $user->givePermissionTo($pulsePermission);
            $message = "Acceso a Pulse otorgado exitosamente";
        } else {
            // Quitar permiso
            $user->revokePermissionTo($pulsePermission);
            $message = "Acceso a Pulse revocado exitosamente";
        }

        return response()->json([
            'message' => 200,
            'message_text' => $message,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'has_pulse_access' => $user->can('access_pulse')
            ]
        ]);
    }

    /**
     * Obtener estadísticas de acceso a Pulse
     */
    public function stats()
    {
        $totalUsers = User::whereHas('roles', function($q) {
            $q->whereNotIn('name', ['Director General']);
        })->count();

        $usersWithAccess = User::permission('access_pulse')
            ->whereHas('roles', function($q) {
                $q->whereNotIn('name', ['Director General']);
            })->count();

        $directorGeneral = User::role('Director General')->count();

        return response()->json([
            'total_users' => $totalUsers,
            'users_with_pulse_access' => $usersWithAccess,
            'users_without_pulse_access' => $totalUsers - $usersWithAccess,
            'director_general_count' => $directorGeneral,
            'access_percentage' => $totalUsers > 0 ? round(($usersWithAccess / $totalUsers) * 100, 2) : 0
        ]);
    }
}
