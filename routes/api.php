<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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

    // 'middleware' => 'auth:api',
    'prefix' => 'auth',
    // 'middleware' => ['role:admin','permission:publish articles'],
], function ($router) {

    //Login
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
    Route::post('/me', [AuthController::class, 'me'])->name('me');
    Route::post('/list', [AuthController::class, 'list']);
    Route::post('/reg', [AuthController::class, 'reg']);
});

Route::group([
    'middleware' => 'auth:api',
], function ($router) {

    //Roles
    Route::resource("roles",RolesController::class);

    //Staffs
    Route::get("staffs/config",[StaffsController::class,"config"]);
    Route::post("staffs/{id}",[StaffsController::class,"update"]);
    Route::resource("staffs",StaffsController::class);

    //Departaments
    Route::resource("departaments",DepartamentController::class);

    //Contracts
    Route::resource("contracts",ContractController::class);

    //Profile
    Route::resource("profile",ProfileController::class);

    Route::get('profile_avatar', [ProfileAvatarController::class, 'show']);

});
