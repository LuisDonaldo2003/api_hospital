<?php

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use App\Mail\VerificationCodeMail;
use App\Mail\RecoveryCodeMail;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileAvatarController;
use App\Http\Controllers\Admin\Rol\RolesController;
use App\Http\Controllers\Admin\Staff\StaffsController;
use App\Http\Controllers\Admin\Profile\ProfileController;
use App\Http\Controllers\Admin\ContractTypes\ContractController;
use App\Http\Controllers\Admin\Departament\DepartamentController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group([
    'prefix' => 'auth',
], function ($router) {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
    Route::post('/me', [AuthController::class, 'me'])->name('me');
    Route::post('/list', [AuthController::class, 'list']);
    Route::post('/reg', [AuthController::class, 'reg']);
});

// ✅ Verificación de cuenta
Route::post('/verify-code', [AuthController::class, 'verifyCode']);

Route::post('/resend-code', function (Request $request) {
    $request->validate(['email' => 'required|email']);

    $user = User::where('email', $request->email)->first();
    if (!$user) {
        return response()->json(['message' => 'Usuario no encontrado.'], 404);
    }

    $user->email_verification_code = strtoupper(Str::random(8));
    $user->email_code_sent_at = now();
    $user->save();

    Mail::to($user->email)->send(new VerificationCodeMail($user));

    return response()->json(['message' => 'Código reenviado con éxito.']);
});

// 🔐 Recuperación de contraseña (usando métodos del AuthController)
Route::post('/forgot-password/send-code', [AuthController::class, 'sendRecoveryCode']);
Route::post('/forgot-password/verify-code', [AuthController::class, 'verifyRecoveryCode']);
Route::post('/forgot-password/reset', [AuthController::class, 'resetPassword']);

Route::group([
    'middleware' => 'auth:api',
], function ($router) {
    // Roles
    Route::resource("roles", RolesController::class);

    // Staffs
    Route::get("staffs/config", [StaffsController::class, "config"]);
    Route::post("staffs/{id}", [StaffsController::class, "update"]);
    Route::resource("staffs", StaffsController::class);

    //Complete-user
    Route::middleware('auth:api')->post('/complete-profile', [StaffsController::class, 'completeProfile']);

    // Departaments
    Route::resource("departaments", DepartamentController::class);

    // Contracts
    Route::resource("contracts", ContractController::class);

    // Profile
    Route::resource("profile", ProfileController::class);
    Route::get('profile_avatar', [ProfileAvatarController::class, 'show']);
});
