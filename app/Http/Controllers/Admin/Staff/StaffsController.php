<?php

namespace App\Http\Controllers\Admin\Staff;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\User\UserResource;
use App\Http\Resources\User\UserCollection;
use App\Models\User;

class StaffsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;
        $users = User::where("name", "like", "%".$search."%")
            ->orWhere("surname", "like", "%".$search."%")
            ->orWhere("email", "like", "%".$search."%")
            ->orderBy("id", "desc")
            ->get();

        return response()->json([
            "users" => UserCollection::make($users),
        ]);
    }

    public function config()
    {
        $roles = Role::all();
        return response()->json([
            "roles" => $roles,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $request->validate([
            'imagen' => 'required|image|mimes:jpg,jpeg,png|max:2048', // Máx 2MB
        ]);

        $users_is_valid = User::where("email", $request->email)->first();

        if ($users_is_valid) {
            return response()->json([
                "message" => 403,
                "message_text" => "El Usuario con este email ya existe"
            ]);
        }

        // Crear un array con los datos validados
        $data = [
            'name' => $request->name,
            'surname' => $request->surname,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'birth_date' => $request->birth_date,
            'gender' => $request->gender,
            'education' => $request->education,
            'designation' => $request->designation,
            'address' => $request->address,
        ];

        if ($request->hasFile("imagen")) {
            $path = $request->file('imagen')->store('staffs', 'public');
            $data["avatar"] = $path;

        }

        if ($request->password) {
            $data["password"] = bcrypt($request->password);
        }

        // Cambio de "birth" a "birth_date"
        if ($request->has('birth_date')) {
            $data['birth_date'] = $request->birth_date;
        }

        $user = User::create($data);
        $role = Role::findOrFail($request->role_id);
        $user->assignRole($role);

        return response()->json([
            "message" => 200,
            "message_text" => "Usuario creado correctamente",
            "user" => new UserResource($user)
        ]);

    }
    /**
     * Update the specified resource in storage.
     */


     public function show($id)
     {
         $user = User::with('roles')->find($id);

         if (!$user) {
             return response()->json([
                 "message" => 404,
                 "message_text" => "Usuario no encontrado"
             ], 404);
         }

         // Verificar si el usuario tiene un avatar almacenado y generar la URL completa
         $user->avatar = $user->avatar ? asset('storage/' . $user->avatar) : null;

         return response()->json([
             "user" => $user
         ]);
     }

    public function update(Request $request, string $id)
    {
        $users_is_valid = User::where("id","<>", $id)->where("email",$request->email)->first();

        if($users_is_valid){
            return response()->json([
                "message" => 403,
                "message_text" => "El Usuario con este email ya existe"
            ]);
        }

        $user = User::findOrFail($id);

        if ($request->hasFile("imagen")) {
            if ($user->avatar) {
                Storage::delete('public/' . $user->avatar); // Asegurar eliminación correcta
            }
            $path = $request->file('imagen')->store('staffs', 'public'); // Guardar en public/storage
            $user->avatar = $path; // Asignar al usuario
        }


        if($request->password){
            $request->merge(["password" => bcrypt($request->password)]);
        }

        if ($request->has('birth_date')) {
            try {
                $data['birth_date'] = Carbon::parse($request->birth_date)->format('Y-m-d');
            } catch (\Exception $e) {
                return response()->json(["error" => "Formato de fecha inválido"], 400);
            }
        }

        $user->update($request->all());


        if($request->role_id != $user->roles()->first()->id){
            $role_old = Role::findOrFail($user->roles()->first()->id);
            $user->removeRole($role_old);
            $role_new = Role::findOrFail($request->role_id);
            $user->assignRole($role_new);

        }

        return response()->json([
            "message" => 200,
            "message_text" => "Usuario creado correctamente",
            "user" => $user
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        if($user->avatar){
            Storage::delete($user->avatar);
        }
        $user->delete();
        return response()->json([
            "message" => 200,
            "message_text" => "Usuario eliminado correctamente",
        ]);
    }
}
