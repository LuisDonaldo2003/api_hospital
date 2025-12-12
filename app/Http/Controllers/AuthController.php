<?php

namespace App\Http\Controllers;

use Str;
use Mail;
use Validator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller;
use App\Mail\VerificationCodeMail;
use App\Http\Resources\User\UserResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => [
            'login', 'register', 'verifyCode',
            'sendRecoveryCode', 'verifyRecoveryCode', 'resetPassword'
        ]]);
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

        // ======================================
        // SISTEMA DE SESIONES CONCURRENTES
        // ======================================
        
        // Generar un nuevo session_id único para esta sesión
        $newSessionId = \Str::uuid()->toString();
        
        // Guardar el nuevo session_id en la base de datos
        $user->session_id = $newSessionId;
        $user->session_created_at = now();
        $user->save();
        
        // Marcar al usuario como en línea con el nuevo session_id
        \Cache::put('user-is-online-' . $user->id, now()->timestamp, 120); // 2 minutos
        \Cache::put('user-session-' . $user->id, $newSessionId, 7200); // 2 horas

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

        return $this->respondWithToken($token, $newSessionId);
    }

    public function me()
    {
        $user = auth()->user();

        // Retornar UserResource para mantener consistencia y exponer gender_id + gender name
        return response()->json([
            'user' => new \App\Http\Resources\User\UserResource($user)
        ]);
    }

    public function list()
    {
        $users = User::all();
        return response()->json([
            "users" => UserResource::collection($users),
        ]);
    }

    public function logout()
    {
        $user = auth('api')->user();
        if ($user) {
            // Limpiar session_id de la base de datos
            $user->session_id = null;
            $user->session_created_at = null;
            $user->save();
            
            // Limpiar caches
            \Cache::forget('user-is-online-' . $user->id);
            \Cache::forget('user-session-' . $user->id);
        }
        auth()->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function heartbeat()
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Obtener el session_id del cliente (enviado en el header o body)
        $clientSessionId = request()->header('X-Session-ID') ?? request()->input('session_id');
        
        // Obtener el session_id actual del usuario en la BD
        $currentSessionId = $user->session_id;
        
        // Verificar si hay un session_id y si coincide con el actual
        if ($clientSessionId && $currentSessionId && $clientSessionId !== $currentSessionId) {
            // La sesión del cliente no es la actual - fue cerrada por otro login
            return response()->json([
                'error' => 'Session closed',
                'message' => 'Tu sesión ha sido cerrada porque iniciaste sesión en otro dispositivo',
                'session_closed' => true
            ], 401);
        }
        
        // Actualizar timestamp de actividad
        \Cache::put('user-is-online-' . $user->id, now()->timestamp, 120); // 2 minutos
        
        return response()->json([
            'message' => 'Heartbeat updated',
            'session_valid' => true
        ]);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    protected function respondWithToken($token, $sessionId = null)
    {
        $user = auth('api')->user();

        $permissions = $user->getAllPermissions()->pluck('name');

        $response = [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'session_id' => $sessionId ?? $user->session_id,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'surname' => $user->surname,
                'email' => $user->email,
                'gender_id' => $user->gender_id,
                'gender' => optional($user->gender)->name,
                'roles' => $user->getRoleNames(),
                'permissions' => $permissions,
            ],
            'is_profile_complete' => $user->isProfileComplete(),
        ];

        return response()->json($response);
    }

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

    public function sendRecoveryCode(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        $code = rand(10000000, 99999999);
        $user->recovery_code = $code;
        $user->recovery_code_expires_at = now()->addMinutes(5);
        $user->save();

        Mail::to($user->email)->send(new \App\Mail\RecoveryCodeMail($user));
        return response()->json(['message' => 'Código de recuperación enviado al correo.']);
    }

    public function verifyRecoveryCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || $user->recovery_code !== $request->code || now()->greaterThan($user->recovery_code_expires_at)) {
            return response()->json(['message' => 'Código incorrecto o expirado.'], 400);
        }

        return response()->json(['message' => 'Código válido.', 'token' => $user->recovery_code]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        $user->password = \Hash::make($request->password);
        $user->recovery_code = null;
        $user->recovery_code_expires_at = null;
        $user->save();

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }

    /**
     * Change password for authenticated users
     * Requires current password verification for security
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ], [
            'current_password.required' => 'La contraseña actual es requerida.',
            'new_password.required' => 'La nueva contraseña es requerida.',
            'new_password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'new_password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $user = auth('api')->user();

        // Verify current password
        if (!\Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'La contraseña actual es incorrecta.'
            ], 400);
        }

        // Update password
        $user->password = \Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Contraseña actualizada correctamente.'
        ]);
    }

}
