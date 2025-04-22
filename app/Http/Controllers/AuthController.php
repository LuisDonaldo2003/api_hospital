<?php

namespace App\Http\Controllers;

use Str;
use Mail;
use Validator;
use App\Models\User;
use App\Mail\VerificationCodeMail;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AuthController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'verifyCode']]);
    }

    public function register()
    {
        $validator = Validator::make(request()->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = new User;
        $user->name = request()->name;
        $user->email = request()->email;
        $user->password = bcrypt(request()->password);
        $user->save();

        return response()->json($user, 201);
    }

    public function reg()
    {
        $this->authorize('create', User::class);

        $validator = Validator::make(request()->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = new User;
        $user->name = request()->name;
        $user->email = request()->email;
        $user->password = bcrypt(request()->password);
        $user->save();

        return response()->json($user, 201);
    }

    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth('api')->user();

        if (is_null($user->email_verified_at)) {
            $expired = true;

            if ($user->email_code_sent_at) {
                $expired = now()->diffInMinutes($user->email_code_sent_at) >= 5;
            }

            if ($expired) {
                $user->email_verification_code = strtoupper(Str::random(8));
                $user->email_code_sent_at = now();
                $user->save();

                Mail::to($user->email)->send(new VerificationCodeMail($user));
            }

            return response()->json([
                'message' => 'Correo no verificado. Se ha enviado un nuevo código de verificación.',
                'status' => 403,
                'unverified' => true
            ], 403);
        }

        return $this->respondWithToken($token);
    }

    public function me()
    {
        return response()->json(auth('api')->user());
    }

    public function list()
    {
        $users = User::all();
        return response()->json([
            "users" => $users,
        ]);
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    protected function respondWithToken($token)
    {
        $permissions = auth("api")->user()->getAllPermissions()->map(function ($perm) {
            return $perm->name;
        });

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            "user" => [
                "name" => auth('api')->user()->name,
                "surname" => auth('api')->user()->surname,
                "email" => auth('api')->user()->email,
                "roles" => auth('api')->user()->getRoleNames(),
                "permissions" => $permissions,
            ],
        ]);
    }

    // ✅ Nuevo método: Verificación con login automático
    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:8'
        ]);

        $user = User::where('email', $request->email)
            ->where('email_verification_code', strtoupper($request->code))
            ->first();

        if (! $user) {
            return response()->json([
                'message' => 'Código de verificación inválido.'
            ], 400);
        }

        $user->email_verified_at = now();
        $user->email_verification_code = null;
        $user->save();

        $token = auth('api')->login($user);
        return $this->respondWithToken($token);
    }
}
