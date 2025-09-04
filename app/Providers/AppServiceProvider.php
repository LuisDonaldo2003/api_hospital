<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Facades\Pulse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
