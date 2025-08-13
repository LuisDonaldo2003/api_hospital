<?php

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\RecoveryCodeMail;
use App\Mail\VerificationCodeMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

// Controladores
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileAvatarController;
use App\Http\Controllers\Admin\Rol\RolesController;
use App\Http\Controllers\Admin\State\StateController;
use App\Http\Controllers\Admin\Staff\StaffsController;
use App\Http\Controllers\Admin\Archive\ArchiveController;
use App\Http\Controllers\Admin\Profile\ProfileController;
use App\Http\Controllers\Admin\Location\LocationController;
use App\Http\Controllers\Admin\ContractTypes\ContractController;
use App\Http\Controllers\Admin\Departament\DepartamentController;
use App\Http\Controllers\Admin\Municipality\MunicipalityController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// 🔐 Autenticación
Route::group([
    'prefix' => 'auth',
], function () {
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

// 🔐 Recuperación de contraseña
Route::post('/forgot-password/send-code', [AuthController::class, 'sendRecoveryCode']);
Route::post('/forgot-password/verify-code', [AuthController::class, 'verifyRecoveryCode']);
Route::post('/forgot-password/reset', [AuthController::class, 'resetPassword']);

// 🔐 Rutas protegidas con token
Route::group([
    'middleware' => 'auth:api',
], function () {

    // ⚠️ Deprecado: antes devolvía genders y estados juntos
    Route::get("archives/config", [ArchiveController::class, "config"]);
    
    // Endpoint de prueba para mapeo de localidades
    Route::post("archives/test-location-mapping", [ArchiveController::class, "testLocationMapping"]);
    // Estadísticas Dashboard Archivo
    Route::get('archives/stats', [ArchiveController::class, 'stats']);

    // ✅ NUEVOS ENDPOINTS
    Route::get('genders', [ArchiveController::class, 'genders']);
    Route::get('states', [ArchiveController::class, 'states']); // sin municipios anidados

    // Municipios y localidades por estado
    Route::get('municipalities', [MunicipalityController::class, 'byState']);
    Route::get('locations', [LocationController::class, 'byMunicipality']);
    Route::get('locations/search', [LocationController::class, 'searchByName']);
    Route::get('locations/search-priority', [LocationController::class, 'searchPriorityOnly']);
    Route::get('locations/auto-detect', [LocationController::class, 'autoDetectLocation']);
    Route::post('locations/find-or-create', [LocationController::class, 'findOrCreateLocationFromText']);

    // Recursos principales
    Route::resource("archives", ArchiveController::class);
    Route::resource("roles", RolesController::class);

    Route::get("staffs/config", [StaffsController::class, "config"]);
    Route::post("staffs/{id}", [StaffsController::class, "update"]);
    Route::resource("staffs", StaffsController::class);
    Route::post('/complete-profile', [StaffsController::class, 'completeProfile']);

    Route::resource("departaments", DepartamentController::class);
    Route::resource("contracts", ContractController::class);
    Route::resource("profile", ProfileController::class);

    // Avatar
    Route::get('profile_avatar', [ProfileAvatarController::class, 'show']);
    Route::put('users/profile_avatar/{id}', [ProfileAvatarController::class, 'update']);

    // Ajustes
    Route::get('settings', [StaffsController::class, 'getSettings']);
    Route::post('settings', [StaffsController::class, 'updateSettings']);
});

// Accesos externos directos
Route::middleware('auth:api')->put('/users/profile_avatar/{id}', [ProfileAvatarController::class, 'update']);
Route::middleware('auth:api')->get('/users/{id}', [StaffsController::class, 'show']);;

// Backup de archivos
Route::prefix('archives/backup')->group(function () {
    Route::post('/upload', [ArchiveController::class, 'uploadBackup']);
    Route::get('/list', [ArchiveController::class, 'listBackups']);
    Route::get('/download/{filename}', [ArchiveController::class, 'downloadBackup']);
});
Route::post('/profile/avatar/{id}', [ProfileAvatarController::class, 'update'])->middleware('auth:api');