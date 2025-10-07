<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MaintenanceUp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hospital:up {--message= : Mensaje personalizado para mostrar} {--redirect= : URL de redirección} {--secret= : Token secreto para bypass}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activa el modo de mantenimiento del sistema hospitalario';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $options = [];
        
        // Usar retry por defecto de 30 minutos
        $options['--retry'] = 1800;
        
        if ($this->option('redirect')) {
            $options['--redirect'] = $this->option('redirect');
        }
        
        if ($this->option('secret')) {
            $options['--secret'] = $this->option('secret');
        }
        
        // Ejecutar el comando down nativo de Laravel
        $exitCode = Artisan::call('down', $options);
        
        if ($exitCode === 0) {
            $this->info('🔧 Sistema hospitalario en modo mantenimiento');
            $this->line('🕐 Tiempo estimado: 30 minutos');
            
            if ($this->option('message')) {
                $this->line('📝 Mensaje: ' . $this->option('message'));
            }
            
            if ($this->option('secret')) {
                $this->warn('🔑 Token de bypass: ' . $this->option('secret'));
            }
            
            // Log del evento
            $this->info('📝 Mantenimiento iniciado: ' . now()->format('Y-m-d H:i:s'));
        } else {
            $this->error('❌ Error al activar el modo mantenimiento');
        }
        
        return $exitCode;
    }
}