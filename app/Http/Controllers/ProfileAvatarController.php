<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\User\UserResource;
use App\Models\User;

class ProfileAvatarController extends Controller
{
    public function show()
    {
        $userId = Auth::id();

        $user = User::with(['roles', 'departament', 'profileRelation', 'contractType'])->findOrFail($userId);
        $roles = $user->roles->pluck('name');

        return response()->json([
            'data' => new UserResource($user),
            'roles' => $roles
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        \Log::info('📥 Request recibido:', $request->all());

        $user->fill($request->only([
            'name', 'surname', 'mobile', 'birth_date', 'gender_id',
            'curp', 'rfc', 'ine', 'attendance_number', 'professional_license',
            'funcion_real', 'departament_id', 'profile_id', 'contract_type_id'
        ]));

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::delete('public/' . $user->avatar);
            }
            $file = $request->file('avatar');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('staffs', $filename, 'public');
            $user->avatar = 'staffs/' . $filename;
        }

        $user->save();

        \Log::info('✅ Usuario actualizado correctamente:', $user->toArray());

        // Forzar recarga con relaciones para evitar nulos
        $user = User::with(['roles', 'departament', 'profileRelation', 'contractType'])->find($user->id);
        $user->avatar = $user->avatar ? asset('storage/' . $user->avatar) : null;

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'data' => new UserResource($user)
        ]);
    }
}
