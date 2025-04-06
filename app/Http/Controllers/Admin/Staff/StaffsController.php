<?php

namespace App\Http\Controllers\Admin\Staff;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Profile;
use App\Models\Departaments;
use App\Models\ContractType;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserCollection;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\User\UserResource;

class StaffsController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;
        $users = User::where("name", "like", "%".$search."%")
            ->orWhere("surname", "like", "%".$search."%")
            ->orWhere("email", "like", "%".$search."%")
            ->orderBy("id", "desc")
            ->get();

        return response()->json([
            "users" => UserResource::collection($users),
        ]);
    }

    public function config()
    {
        $roles = Role::all();
        $departaments = Departaments::select("id", "name")->get();
        $profiles = Profile::select("id", "name")->get();
        $contractTypes = ContractType::select("id", "name")->get();

        return response()->json([
            "roles" => $roles,
            "departaments" => $departaments,
            "profiles" => $profiles,
            "contract_types" => $contractTypes,
        ]);
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

        if (User::where("email", $request->email)->exists()) {
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
            $data["avatar"] = $request->file('imagen')->store('staffs', 'public');
        }

        if ($request->password) {
            $data["password"] = bcrypt($request->password);
        }

        $user = User::create($data);
        $user->assignRole(Role::findOrFail($request->role_id));

        return response()->json([
            "message" => 200,
            "message_text" => "Usuario creado correctamente",
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

        return response()->json([
            "user" => $user
        ]);
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

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

        if ($request->role_id && $request->role_id != $user->roles()->first()->id) {
            $user->syncRoles([Role::findOrFail($request->role_id)]);
        }

        return response()->json([
            "message" => 200,
            "message_text" => "Usuario actualizado correctamente",
            "user" => new UserResource($user)
        ]);
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        if ($user->avatar) {
            Storage::delete('public/' . $user->avatar);
        }
        $user->delete();
        return response()->json([
            "message" => 200,
            "message_text" => "Usuario eliminado correctamente",
        ]);
    }
}
