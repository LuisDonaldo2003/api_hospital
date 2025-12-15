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
use App\Http\Controllers\Admin\RoleFamily\RoleFamilyController;
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
use App\Http\Controllers\API\TeachingController;
use App\Http\Controllers\API\EvaluacionController;
use App\Http\Controllers\API\ModalidadController;
use App\Http\Controllers\API\ParticipacionController;
use App\Http\Controllers\API\AreaController;
use App\Http\Controllers\API\DoctorController;
use App\Http\Controllers\API\CitaController;
use App\Http\Controllers\API\EspecialidadController;
use App\Http\Controllers\API\GeneralMedicalController;
use App\Http\Controllers\LicenseController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rutas de mantenimiento (accesibles siempre)
Route::get('/maintenance/status', [MaintenanceController::class, 'status']);
Route::get('/maintenance/check', [MaintenanceController::class, 'check']);

// Rutas de licencia (accesibles siempre para verificar estado)
Route::get('/license/status', [LicenseController::class, 'status']);
Route::get('/license/info', [LicenseController::class, 'info']);
Route::get('/license/hardware-info', [LicenseController::class, 'hardwareInfo']);
Route::get('/license/check-feature', [LicenseController::class, 'checkFeature']);
Route::post('/license/upload', [LicenseController::class, 'upload']); // Sin autenticación para permitir activación inicial

// Rutas de gestión de licencia (requiere autenticación)
Route::middleware('auth:api')->group(function () {
    Route::get('/license/history', [LicenseController::class, 'history']);
    Route::get('/license/activations', [LicenseController::class, 'activations']);
});

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
    Route::post('/change-password', [AuthController::class, 'changePassword'])->name('change-password');
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

// Rutas protegidas con token y verificación de licencia
Route::group([
    'middleware' => ['auth:api', 'check.license'],
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
    Route::resource("role-families", RoleFamilyController::class);
    Route::post("role-families/{id}/assign-roles", [RoleFamilyController::class, "assignRoles"]);

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

    // Rutas para Módulo de Enseñanzas (teachings)
    Route::prefix('teachings')->group(function () {
        Route::get('/', [TeachingController::class, 'index']);
        Route::get('/stats', [TeachingController::class, 'stats']);
        Route::get('/export/excel', [TeachingController::class, 'export']);
        Route::post('/import/excel', [TeachingController::class, 'import']);
        
        // Catálogos - Solo lectura
        Route::get('/modalidades', [TeachingController::class, 'getModalidades']);
        Route::get('/participaciones', [TeachingController::class, 'getParticipaciones']);
        Route::get('/profesiones', [TeachingController::class, 'getProfesiones']);
        Route::get('/areas', [TeachingController::class, 'getAreas']);

        // CRUD Modalidades
        Route::prefix('catalogs/modalidades')->group(function () {
            Route::get('/', [ModalidadController::class, 'index']);
            Route::get('/{id}', [ModalidadController::class, 'show']);
            Route::post('/', [ModalidadController::class, 'store']);
            Route::put('/{id}', [ModalidadController::class, 'update']);
            Route::delete('/{id}', [ModalidadController::class, 'destroy']);
            Route::patch('/{id}/toggle', [ModalidadController::class, 'toggleStatus']);
        });

        // CRUD Participaciones
        Route::prefix('catalogs/participaciones')->group(function () {
            Route::get('/', [ParticipacionController::class, 'index']);
            Route::get('/{id}', [ParticipacionController::class, 'show']);
            Route::post('/', [ParticipacionController::class, 'store']);
            Route::put('/{id}', [ParticipacionController::class, 'update']);
            Route::delete('/{id}', [ParticipacionController::class, 'destroy']);
            Route::patch('/{id}/toggle', [ParticipacionController::class, 'toggleStatus']);
        });

        // CRUD Áreas
        Route::prefix('catalogs/areas')->group(function () {
            Route::get('/', [AreaController::class, 'index']);
            Route::get('/{id}', [AreaController::class, 'show']);
            Route::post('/', [AreaController::class, 'store']);
            Route::put('/{id}', [AreaController::class, 'update']);
            Route::delete('/{id}', [AreaController::class, 'destroy']);
            Route::patch('/{id}/toggle', [AreaController::class, 'toggleStatus']);
        });

        // Evaluaciones (rutas específicas primero, luego genéricas)
        Route::get('/evaluaciones/stats', [EvaluacionController::class, 'stats']);
        Route::get('/evaluaciones/pendientes', [EvaluacionController::class, 'pendientes']);
        Route::post('/evaluaciones', [EvaluacionController::class, 'store']);
        Route::get('/evaluaciones/{id}', [EvaluacionController::class, 'show']);
        Route::put('/evaluaciones/{id}', [EvaluacionController::class, 'update']);
        Route::delete('/evaluaciones/{id}', [EvaluacionController::class, 'destroy']);
        Route::get('/evaluaciones', [EvaluacionController::class, 'index']);

        // CRUD de teachings
        Route::get('/{id}', [TeachingController::class, 'show']);
        Route::post('/', [TeachingController::class, 'store']);
        Route::put('/{id}', [TeachingController::class, 'update']);
        Route::delete('/{id}', [TeachingController::class, 'destroy']);
    });

    // Rutas para Módulo de Citas Médicas (appointments)
    Route::prefix('appointments')->group(function () {
        // Especialidades
        Route::prefix('especialidades')->group(function () {
            Route::get('/', [EspecialidadController::class, 'index']);
            Route::get('/{id}', [EspecialidadController::class, 'show']);
            Route::post('/', [EspecialidadController::class, 'store']);
            Route::put('/{id}', [EspecialidadController::class, 'update']);
            Route::delete('/{id}', [EspecialidadController::class, 'destroy']);
        });

        // Médicos Generales
        Route::prefix('general-medicals')->group(function () {
            Route::get('/', [GeneralMedicalController::class, 'index']);
            Route::get('/{id}', [GeneralMedicalController::class, 'show']);
            Route::post('/', [GeneralMedicalController::class, 'store']);
            Route::put('/{id}', [GeneralMedicalController::class, 'update']);
            Route::delete('/{id}', [GeneralMedicalController::class, 'destroy']);
        });

        // Doctores
        Route::prefix('doctors')->group(function () {
            Route::get('/', [DoctorController::class, 'index']);
            Route::get('/stats', [DoctorController::class, 'stats']);
            Route::get('/especialidades', [DoctorController::class, 'listEspecialidades']);
            Route::get('/by-especialidad/{especialidadId}', [DoctorController::class, 'getByEspecialidad']);
            Route::get('/{id}', [DoctorController::class, 'show']);
            Route::post('/', [DoctorController::class, 'store']);
            Route::put('/{id}', [DoctorController::class, 'update']);
            Route::delete('/{id}', [DoctorController::class, 'destroy']);
        });

        // Citas
        Route::prefix('citas')->group(function () {
            Route::get('/', [CitaController::class, 'index']);
            Route::get('/stats', [CitaController::class, 'stats']);
            Route::get('/today', [CitaController::class, 'today']);
            Route::get('/{id}', [CitaController::class, 'show']);
            Route::post('/', [CitaController::class, 'store']);
            Route::put('/{id}', [CitaController::class, 'update']);
            Route::post('/{id}/cancel', [CitaController::class, 'cancel']);
            Route::delete('/{id}', [CitaController::class, 'destroy']);
        });

        // Horarios disponibles
        Route::get('/horarios-disponibles', [CitaController::class, 'getHorariosDisponibles']);
    });
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

// Ruta simple para verificar si el sistema está activo (no en mantenimiento)
Route::get('/health-check', function () {
    return response()->json(['status' => 'ok', 'message' => 'Sistema activo'], 200);
});