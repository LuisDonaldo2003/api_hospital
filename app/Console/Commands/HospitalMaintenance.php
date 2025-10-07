<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class HospitalMaintenance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hospital:mode 
                           {action : up/down/status para activar/desactivar/verificar mantenimiento}
                           {--message= : Mensaje personalizado para mostrar}
                           {--secret= : Token secreto para bypass}
                           {--redirect= : URL de redirección}
                           {--retry=1800 : Segundos después de los cuales se puede reintentar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gestiona el modo de mantenimiento del sistema hospitalario (up=activar, down=desactivar, status=verificar)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        
        if (!in_array($action, ['up', 'down', 'status'])) {
            $this->error('❌ Acción no válida. Use "up" para activar, "down" para desactivar o "status" para verificar el mantenimiento.');
            return Command::FAILURE;
        }
        
        if ($action === 'up') {
            return $this->activateMaintenanceMode();
        } elseif ($action === 'down') {
            return $this->deactivateMaintenanceMode();
        } else {
            return $this->checkMaintenanceStatus();
        }
    }
    
    /**
     * Activa el modo de mantenimiento
     */
    private function activateMaintenanceMode()
    {
        $options = [];
        
        // Configurar retry
        $options['--retry'] = $this->option('retry');
        
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
            $this->line('🕐 Tiempo estimado de reintento: ' . $this->formatTime($this->option('retry')));
            
            if ($this->option('message')) {
                $this->line('📝 Mensaje personalizado: ' . $this->option('message'));
            }
            
            if ($this->option('secret')) {
                $this->warn('🔑 Token de bypass: ' . $this->option('secret'));
                $this->line('   Acceso directo: ' . config('app.url') . '?secret=' . $this->option('secret'));
            }
            
            $this->info('📝 Mantenimiento iniciado: ' . now()->format('Y-m-d H:i:s'));
            $this->line('');
            $this->line('💡 Para desactivar: php artisan hospital:mode down');
        } else {
            $this->error('❌ Error al activar el modo mantenimiento');
        }
        
        return $exitCode;
    }
    
    /**
     * Desactiva el modo de mantenimiento
     */
    private function deactivateMaintenanceMode()
    {
        $exitCode = Artisan::call('up');
        
        if ($exitCode === 0) {
            $this->info('✅ Sistema hospitalario reactivado');
            $this->line('🚀 El sistema está ahora disponible para todos los usuarios');
            $this->info('📝 Mantenimiento finalizado: ' . now()->format('Y-m-d H:i:s'));
        } else {
            $this->error('❌ Error al desactivar el modo mantenimiento');
        }
        
        return $exitCode;
    }
    
    /**
     * Verifica el estado actual del mantenimiento
     */
    private function checkMaintenanceStatus()
    {
        $isDown = app()->isDownForMaintenance();
        
        if ($isDown) {
            $this->error('🔧 SISTEMA EN MANTENIMIENTO');
            $this->line('');
            
            // Intentar leer información del archivo de mantenimiento
            try {
                $maintenanceFile = storage_path('framework/maintenance.php');
                if (file_exists($maintenanceFile)) {
                    $payload = include $maintenanceFile;
                    
                    if (isset($payload['data'])) {
                        $data = $payload['data'];
                        
                        $this->line('📝 Información del mantenimiento:');
                        if (isset($data['message'])) {
                            $this->line('   Mensaje: ' . $data['message']);
                        }
                        if (isset($data['retry'])) {
                            $this->line('   Tiempo de reintento: ' . $this->formatTime($data['retry']));
                        }
                        if (isset($data['time'])) {
                            $this->line('   Iniciado: ' . date('Y-m-d H:i:s', $data['time']));
                        }
                        if (isset($data['allowed']) && !empty($data['allowed'])) {
                            $this->line('   IPs permitidas: ' . implode(', ', $data['allowed']));
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->warn('⚠️ No se pudo leer información adicional del mantenimiento');
            }
            
            $this->line('');
            $this->line('💡 Para desactivar: php artisan hospital:mode down');
            
            return Command::FAILURE; // Indica que está en mantenimiento
        } else {
            $this->info('✅ SISTEMA ACTIVO');
            $this->line('🚀 El sistema está funcionando normalmente');
            $this->line('📝 Estado verificado: ' . now()->format('Y-m-d H:i:s'));
            $this->line('');
            $this->line('💡 Para activar mantenimiento: php artisan hospital:mode up');
            
            return Command::SUCCESS;
        }
    }
    
    /**
     * Formatea el tiempo en un formato legible
     */
    private function formatTime($seconds)
    {
        if ($seconds < 60) {
            return "$seconds segundos";
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            return "$minutes minutos";
        } else {
            $hours = floor($seconds / 3600);
            $remainingMinutes = floor(($seconds % 3600) / 60);
            return $remainingMinutes > 0 ? "$hours horas y $remainingMinutes minutos" : "$hours horas";
        }
    }
}