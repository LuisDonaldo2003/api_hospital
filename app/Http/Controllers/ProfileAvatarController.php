<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\User\UserResource;

class ProfileAvatarController extends Controller
{
    public function show()
{
    $user = Auth::user();
    $roles = $user->roles->pluck('name');

    return response()->json([
        'data' => new UserResource($user),
        'roles' => $roles
    ], 200);
}
}
