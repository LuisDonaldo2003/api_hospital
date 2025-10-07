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
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\ProfileAvatarController;
use App\Http\Controllers\Admin\Rol\RolesController;
use App\Http\Controllers\Admin\State\StateController;
use App\Http\Controllers\Admin\Staff\StaffsController;
use App\Http\Controllers\Admin\Archive\ArchiveController;
use App\Http\Controllers\Admin\Profile\ProfileController;
use App\Http\Controllers\Admin\Location\LocationController;
use App\Http\Controllers\Admin\ContractTypes\ContractController;
use App\Http\Controllers\Admin\Departament\DepartamentController;
use App\Http\Controllers\Admin\PulseAccessController;
use App\Http\Controllers\API\PersonalController;
use App\Http\Controllers\API\PersonalDocumentController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rutas de mantenimiento (accesibles siempre)
Route::get('/maintenance/status', [MaintenanceController::class, 'status']);
Route::get('/maintenance/check', [MaintenanceController::class, 'check']);

// Autenticación
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
    Route::post('/heartbeat', [AuthController::class, 'heartbeat'])->name('heartbeat');
});

// Verificación de cuenta
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

// Recuperación de contraseña
Route::post('/forgot-password/send-code', [AuthController::class, 'sendRecoveryCode']);
Route::post('/forgot-password/verify-code', [AuthController::class, 'verifyRecoveryCode']);
Route::post('/forgot-password/reset', [AuthController::class, 'resetPassword']);

// Rutas protegidas con token
Route::group([
    'middleware' => 'auth:api',
], function () {

    // Estadísticas Dashboard Archivo
    Route::get('archives/stats', [ArchiveController::class, 'stats']);

    // NUEVOS ENDPOINTS
    Route::get('genders', [ArchiveController::class, 'genders']);
    Route::get('states', [ArchiveController::class, 'states']);
    Route::get('municipalities', [ArchiveController::class, 'municipalities']);
    Route::get('locations', [ArchiveController::class, 'locations']);

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

    // Gestión de acceso a Pulse (solo Director General)
    Route::middleware('role:Director General')->group(function () {
        Route::get('pulse-access', [PulseAccessController::class, 'index']);
        Route::post('pulse-access/{userId}/toggle', [PulseAccessController::class, 'togglePulseAccess']);
        Route::get('pulse-access/stats', [PulseAccessController::class, 'stats']);
    });

    // Gestión de Personal y Recursos Humanos
    Route::prefix('personal')->group(function () {
        Route::get('estadisticas', [PersonalController::class, 'estadisticas']);
        Route::post('with-documents', [PersonalController::class, 'storeWithDocuments']);
        Route::get('tipos-documentos', [PersonalDocumentController::class, 'tiposDocumentos']);
        Route::get('{personalId}/documentos/estado', [PersonalDocumentController::class, 'estadoDocumentos']);
        Route::get('{personalId}/documentos', [PersonalDocumentController::class, 'index']);
        Route::post('documentos', [PersonalDocumentController::class, 'store']);
        Route::get('documentos/{id}', [PersonalDocumentController::class, 'show']);
        Route::get('documentos/{id}/download', [PersonalDocumentController::class, 'download']);
        Route::delete('documentos/{id}', [PersonalDocumentController::class, 'destroy']);
    });
    Route::resource('personal', PersonalController::class);
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

// Actividades de usuarios
Route::prefix('user-activities')->middleware('auth:api')->group(function () {
    Route::get('/stats', [App\Http\Controllers\UserActivityController::class, 'getStats']);
    Route::get('/', [App\Http\Controllers\UserActivityController::class, 'getRecentActivities']);
    Route::get('/by-user', [App\Http\Controllers\UserActivityController::class, 'getActivitiesByUser']);
    Route::get('/by-module', [App\Http\Controllers\UserActivityController::class, 'getActivitiesByModule']);
    Route::get('/by-action', [App\Http\Controllers\UserActivityController::class, 'getActivitiesByActionType']);
    Route::get('/most-active', [App\Http\Controllers\UserActivityController::class, 'getMostActiveUsers']);
    Route::post('/', [App\Http\Controllers\UserActivityController::class, 'logActivity']);
    Route::get('/{id}', [App\Http\Controllers\UserActivityController::class, 'getActivityDetail']);
    Route::get('/user/{userId}', [App\Http\Controllers\UserActivityController::class, 'getUserActivities']);
});

Route::post('/profile/avatar/{id}', [ProfileAvatarController::class, 'update'])->middleware('auth:api');