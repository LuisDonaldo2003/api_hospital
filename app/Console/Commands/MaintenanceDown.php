<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MaintenanceDown extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hospital:down';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Desactiva el modo de mantenimiento del sistema hospitalario';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Ejecutar el comando up nativo de Laravel
        Artisan::call('up');
        
        $this->info('✅ Sistema hospitalario reactivado');
        $this->line('🚀 El sistema está ahora disponible para todos los usuarios');
        
        // Log del evento
        $this->info('📝 Mantenimiento finalizado: ' . now()->format('Y-m-d H:i:s'));
        
        return Command::SUCCESS;
    }
}