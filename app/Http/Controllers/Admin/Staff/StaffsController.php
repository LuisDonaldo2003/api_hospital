<?php

namespace App\Http\Controllers\Admin\Staff;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Profile;
use App\Models\ContractType;
use App\Models\Departaments;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\User\UserResource;
use Illuminate\Support\Str;
use App\Services\ActivityLoggerService;

class StaffsController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where("name", "like", "%{$search}%")
                  ->orWhere("surname", "like", "%{$search}%")
                  ->orWhere("email", "like", "%{$search}%");
            });
        }

        $users = $query->orderBy("id", "desc")->get();

        // Log the list action
        ActivityLoggerService::logRead('Staff', null, 'staff', [
            'search_term' => $request->search,
            'total_results' => $users->count()
        ]);

        return response()->json([
            "users" => UserResource::collection($users),
        ]);
    }

    public function config()
    {
        $user = auth()->user();
        
        // Definir roles de super admin que pueden ver todos los roles
        $superAdminRoles = ['Director General', 'Subdirector General', 'Desarrollador'];
        
        // Verificar si el usuario tiene un rol de super admin
        $isSuperAdmin = $user->roles->pluck('name')->intersect($superAdminRoles)->isNotEmpty();
        
        if ($isSuperAdmin) {
            // Super admins ven todos los roles
            $availableRoles = Role::all();
        } else {
            // Obtener familias de roles desde la base de datos con sus roles
            $roleFamilies = \App\Models\RoleFamily::with('roles')->get();
            
            if ($roleFamilies->isEmpty()) {
                // Si no hay familias configuradas, usar lógica legacy
                $availableRoles = $this->getLegacyFilteredRoles($user);
            } else {
                // Obtener el(los) rol(es) actual(es) del usuario
                $userRoles = $user->roles;
                
                // Identificar a qué familias pertenece el usuario basándose en sus roles
                $userFamilyIds = [];
                foreach ($roleFamilies as $family) {
                    // Verificar si alguno de los roles del usuario está en esta familia
                    foreach ($userRoles as $userRole) {
                        if ($family->roles->contains('id', $userRole->id)) {
                            $userFamilyIds[] = $family->id;
                            break; // Ya encontramos que esta familia contiene un rol del usuario
                        }
                    }
                }
                
                // Si el usuario no pertenece a ninguna familia, usar lógica legacy
                if (empty($userFamilyIds)) {
                    $availableRoles = $this->getLegacyFilteredRoles($user);
                } else {
                    // Obtener TODOS los roles que pertenecen a las familias del usuario
                    $availableRoles = collect();
                    $userFamilies = $roleFamilies->whereIn('id', $userFamilyIds);
                    
                    foreach ($userFamilies as $family) {
                        foreach ($family->roles as $role) {
                            // Evitar duplicados (un rol podría estar en múltiples familias)
                            if (!$availableRoles->contains('id', $role->id)) {
                                $availableRoles->push($role);
                            }
                        }
                    }
                }
            }
        }
        
        return response()->json([
            "roles" => $availableRoles,
            "departaments" => Departaments::select("id", "name")->get(),
            "profiles" => Profile::select("id", "name")->get(),
            "contractTypes" => ContractType::select("id", "name")->get(),
        ]);
    }
    
    /**
     * Método legacy para filtrar roles cuando no hay familias en la BD
     */
    private function getLegacyFilteredRoles($user)
    {
        $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();
        
        $userPermissionFamilies = collect($userPermissions)
            ->map(function ($permission) {
                $parts = explode('_', $permission);
                return $parts[0];
            })
            ->unique()
            ->toArray();
        
        $userPermissionSuffixes = collect($userPermissions)
            ->map(function ($permission) {
                $parts = explode('_', $permission, 2);
                return count($parts) > 1 ? $parts[1] : null;
            })
            ->filter()
            ->unique()
            ->toArray();
        
        $userPermissionKeywords = array_merge($userPermissionFamilies, $userPermissionSuffixes);
        
        return Role::with('permissions')->get()->filter(function ($role) use ($userPermissionKeywords) {
            $rolePermissions = $role->permissions->pluck('name')->toArray();
            
            if (empty($rolePermissions)) {
                return false;
            }
            
            foreach ($rolePermissions as $rolePermission) {
                $roleParts = explode('_', $rolePermission);
                $rolePrefix = $roleParts[0];
                $roleSuffix = count($roleParts) > 1 ? implode('_', array_slice($roleParts, 1)) : null;
                
                if (in_array($rolePrefix, $userPermissionKeywords) || 
                    ($roleSuffix && in_array($roleSuffix, $userPermissionKeywords))) {
                    return true;
                }
            }
            
            return false;
        })->values();
    }

    public function store(Request $request)
    {
        $request->validate([
            'imagen' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'email' => 'required|email',
            'name' => 'required|string|max:255',
            'role_id' => 'required|exists:roles,id',
            'surname' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|max:15',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|string|max:10',
            'curp' => 'nullable|string|max:18',
            'ine' => 'nullable|string|max:18',
            'rfc' => 'nullable|string|max:13',
            'attendance_number' => 'nullable|string|max:20',
            'professional_license' => 'nullable|string|max:20',
            'funcion_real' => 'nullable|string|max:255',
            'departament_id' => 'nullable|integer|exists:departaments,id',
            'profile_id' => 'nullable|integer|exists:profiles,id',
            'contract_type_id' => 'nullable|integer|exists:contract_types,id',
        ]);

        $email = strtolower($request->email);

        $allowedDomains = [
            'gmail.com', 'hotmail.com', 'outlook.com', 'yahoo.com',
            'icloud.com', 'live.com', 'protonmail.com',
            'gmx.com', 'mail.com', 'yahoo.com.mx',
            'hotmail.com.mx', 'outlook.com.mx', 'live.com.mx',
            'outlook.es', 'hotmail.es', 'yahoo.es', 'live.com.es',
            'aol.com', 'msn.com', 'yandex.com', 'mail.ru',
            'fastmail.com', 'tutanota.com', 'zoho.com'
        ];
        $emailDomain = substr(strrchr($email, "@"), 1);
        if (!in_array($emailDomain, $allowedDomains)) {
            return response()->json([
                "message" => 403,
                "message_text" => "El dominio del correo no está permitido. Usa un correo como Gmail, Outlook, etc."
            ]);
        }

        if (User::where("email", $email)->exists()) {
            return response()->json([
                "message" => 403,
                "message_text" => "El Usuario con este email ya existe"
            ]);
        }

        $data = $request->only([
            'name', 'surname', 'mobile', 'birth_date', 'gender',
            'curp', 'ine', 'rfc', 'attendance_number', 'professional_license',
            'funcion_real', 'departament_id', 'profile_id', 'contract_type_id'
        ]);
        $data['email'] = $email;
        $data['email_verification_code'] = Str::upper(Str::random(8));

        if ($request->hasFile("imagen")) {
            $data["avatar"] = $request->file('imagen')->store('staffs', 'public');
        }

        if ($request->password) {
            $data["password"] = bcrypt($request->password);
        }

        $user = User::create($data);
        $user->assignRole(Role::findOrFail($request->role_id));

        // Log the creation activity
        ActivityLoggerService::logCreate('User', $user->id, 'staff', [
            'name' => $user->name,
            'surname' => $user->surname,
            'email' => $user->email,
            'role' => $user->roles->first()?->name,
            'departament_id' => $user->departament_id
        ]);

        return response()->json([
            "message" => 200,
            "message_text" => "Usuario creado correctamente. Se envió un código de verificación al correo.",
            "user" => new UserResource($user)
        ]);
    }

    public function show($id)
    {
        $user = User::with('roles')->find($id);

        if (!$user) {
            return response()->json([
                "message" => 404,
                "message_text" => "Usuario no encontrado"
            ], 404);
        }

        $user->avatar = $user->avatar ? asset('storage/' . $user->avatar) : null;

        // Log the read activity
        ActivityLoggerService::logRead('User', $user->id, 'staff', [
            'name' => $user->name,
            'surname' => $user->surname,
            'email' => $user->email,
            'role' => $user->roles->first()?->name
        ]);

        return response()->json([
            "user" => new UserResource($user)
        ]);
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        // Store old values for logging
        $oldValues = [
            'name' => $user->name,
            'surname' => $user->surname,
            'email' => $user->email,
            'role' => $user->roles->first()?->name,
            'departament_id' => $user->departament_id,
            'profile_id' => $user->profile_id,
            'contract_type_id' => $user->contract_type_id
        ];

        if (User::where("id", "<>", $id)->where("email", $request->email)->exists()) {
            return response()->json([
                "message" => 403,
                "message_text" => "El Usuario con este email ya existe"
            ]);
        }

        $data = $request->only([
            'name', 'surname', 'email', 'mobile', 'birth_date', 'gender',
            'curp', 'ine', 'rfc', 'attendance_number', 'professional_license',
            'funcion_real', 'departament_id', 'profile_id', 'contract_type_id'
        ]);

        if ($request->hasFile("imagen")) {
            if ($user->avatar) {
                Storage::delete('public/' . $user->avatar);
            }
            $data["avatar"] = $request->file('imagen')->store('staffs', 'public');
        }

        if ($request->password) {
            $data["password"] = bcrypt($request->password);
        }

        if ($request->has('birth_date')) {
            try {
                $data['birth_date'] = Carbon::parse($request->birth_date)->format('Y-m-d');
            } catch (\Exception $e) {
                return response()->json(["error" => "Formato de fecha inválido"], 400);
            }
        }

        $user->update($data);

        // Siempre sincroniza el rol si viene en la petición
        if ($request->role_id) {
            $user->syncRoles([Role::findOrFail($request->role_id)]);
        }

        // Refresh user to get updated relations
        $user->refresh();
        $user->load('roles');

        // Store new values for logging
        $newValues = [
            'name' => $user->name,
            'surname' => $user->surname,
            'email' => $user->email,
            'role' => $user->roles->first()?->name,
            'departament_id' => $user->departament_id,
            'profile_id' => $user->profile_id,
            'contract_type_id' => $user->contract_type_id
        ];

        // Log the update activity
        ActivityLoggerService::logUpdate('User', $user->id, 'staff', $oldValues, $newValues);

        return response()->json([
            "message" => 200,
            "message_text" => "Usuario actualizado correctamente",
            "user" => new UserResource($user)
        ]);
    }

    public function completeProfile(Request $request)
    {
    $user = auth()->user();

    \Log::info('📥 completeProfile request by user: ' . ($user?->id ?? 'guest'), $request->all());

        $request->validate([
            // Solo estos cinco son obligatorios según la UI
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'mobile' => 'required|string|max:15',
            'birth_date' => 'required|date',
            'gender_id' => 'required|integer|exists:genders,id',
            'attendance_number' => 'required|string|max:20',

            // Los demás son opcionales: si el usuario los provee, se validan; si no, se omiten
            'curp' => 'nullable|string|max:18',
            'ine' => 'nullable|string|max:18',
            'rfc' => 'nullable|string|max:13',
            'professional_license' => 'nullable|string|max:20',
            'funcion_real' => 'nullable|string|max:255',
            'departament_id' => 'nullable|integer|exists:departaments,id',
            'profile_id' => 'nullable|integer|exists:profiles,id',
            'contract_type_id' => 'nullable|integer|exists:contract_types,id',
        ]);

        $data = $request->only([
            'mobile', 'birth_date', 'gender_id', 'curp', 'ine', 'rfc',
            'attendance_number', 'professional_license', 'funcion_real',
            'departament_id', 'profile_id', 'contract_type_id'
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::delete('public/' . $user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('staffs', 'public');
        }

        $user->update($data);

        return response()->json([
            "message" => "Perfil completado exitosamente.",
            "user" => new UserResource($user)
        ]);
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        
        // Log the deletion activity
        ActivityLoggerService::logDelete('User', $user->id, 'staff', [
            'name' => $user->name,
            'surname' => $user->surname,
            'email' => $user->email,
            'role' => $user->roles->first()?->name,
            'departament_id' => $user->departament_id
        ]);

        if ($user->avatar) {
            Storage::delete('public/' . $user->avatar);
        }
        $user->delete();

        return response()->json([
            "message" => 200,
            "message_text" => "Usuario eliminado correctamente",
        ]);
    }

    public function getSettings(Request $request)
    {
        $user = $request->user();
        return response()->json($user->settings ?? []);
    }

    public function updateSettings(Request $request)
    {
        $user = $request->user();
        $user->settings = array_merge($user->settings ?? [], $request->all());
        $user->save();
        return response()->json(['success' => true]);
    }
}
