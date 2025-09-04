<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PulseAuthController;

Route::get('/', function () {
    return view('welcome');
});

// Rutas para autenticación de Laravel Pulse
Route::get('/pulse/login', [PulseAuthController::class, 'showLogin'])->name('pulse.login');
Route::post('/pulse/authenticate', [PulseAuthController::class, 'authenticate'])->name('pulse.authenticate');
Route::post('/pulse/logout', [PulseAuthController::class, 'logout'])->name('pulse.logout');
Route::get('/pulse/check-auth', [PulseAuthController::class, 'checkAuth'])->name('pulse.check-auth');
