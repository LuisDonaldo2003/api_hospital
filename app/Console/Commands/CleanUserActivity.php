<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Models\User;

class CleanUserActivity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:clean-activity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia los registros de actividad obsoletos de usuarios';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando limpieza de actividad de usuarios...');
        
        // Obtener todos los usuarios
        $users = User::all();
        $cleanedCount = 0;
        
        foreach ($users as $user) {
            $cacheKey = 'user-is-online-' . $user->id;
            $timestamp = Cache::get($cacheKey);
            
            if ($timestamp) {
                // Si el timestamp es más antiguo que 2 minutos, eliminar de caché
                $timeDiff = now()->timestamp - $timestamp;
                if ($timeDiff > 120) { // 2 minutos
                    Cache::forget($cacheKey);
                    $cleanedCount++;
                    $this->line("Usuario desconectado: {$user->name} (inactivo por " . round($timeDiff / 60, 1) . " minutos)");
                }
            }
        }
        
        $this->info("Limpieza completada. {$cleanedCount} usuarios marcados como desconectados.");
        
        return 0;
    }
}
