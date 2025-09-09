<?php

namespace App\Providers;

use App\Services\ActivityReportService;
use App\Services\MissedReportRecoveryService;
use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Facades\Pulse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar el servicio de reportes de actividad
        $this->app->singleton(ActivityReportService::class, function ($app) {
            return new ActivityReportService();
        });
        
        // Registrar el servicio de recuperación de reportes perdidos
        $this->app->singleton(MissedReportRecoveryService::class, function ($app) {
            return new MissedReportRecoveryService($app->make(ActivityReportService::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configurar zona horaria por defecto para toda la aplicación
        date_default_timezone_set(config('app.timezone'));
        
        // Configurar cómo Laravel Pulse resuelve los avatares de usuario
        Pulse::user(fn ($user) => [
            'name' => $user->name . ($user->surname ? ' ' . $user->surname : ''),
            'extra' => $user->email,
            'avatar' => $user->avatar 
                ? asset('storage/' . $user->avatar) 
                : 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user->email))) . '?s=40&d=mp&r=g',
        ]);
    }
}
