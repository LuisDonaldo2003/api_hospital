<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\License;
use App\Services\LicenseValidator;
use Carbon\Carbon;

class CheckExpiredLicenses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'license:check-expired
                            {--deactivate : Desactiva automáticamente las licencias expiradas}
                            {--notify : Envía notificaciones sobre licencias próximas a expirar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica el estado de las licencias del sistema y desactiva las expiradas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Verificando estado de licencias del sistema...');
        $this->newLine();

        // Obtener todas las licencias activas
        $activeLicenses = License::where('is_active', true)->get();

        if ($activeLicenses->isEmpty()) {
            $this->warn('⚠️  No hay licencias activas en el sistema.');
            return Command::SUCCESS;
        }

        $this->info("📋 Licencias activas encontradas: {$activeLicenses->count()}");
        $this->newLine();

        $expiredCount = 0;
        $expiringCount = 0;
        $validCount = 0;

        foreach ($activeLicenses as $license) {
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("Licencia ID: {$license->id}");
            $this->line("Institución: {$license->institution}");
            $this->line("Tipo: " . strtoupper($license->type));
            $this->line("Activada: {$license->activated_at->format('d/m/Y H:i')}");
            
            if ($license->type === 'permanent') {
                $this->line("Expiración: <fg=green>PERMANENTE</>");
                $this->line("Estado: <fg=green>✓ VÁLIDA</>");
                $validCount++;
            } else {
                $this->line("Expira: {$license->expires_at->format('d/m/Y H:i')}");
                
                $daysRemaining = $license->daysRemaining();
                
                if ($daysRemaining === 0 || !$license->isValid()) {
                    $this->line("Estado: <fg=red>✗ EXPIRADA</>");
                    $expiredCount++;
                    
                    if ($this->option('deactivate')) {
                        $license->deactivate();
                        $this->warn("⚠️  Licencia desactivada automáticamente.");
                        
                        // Limpiar caché
                        LicenseValidator::clearCache();
                    }
                } elseif ($daysRemaining <= 7) {
                    $this->line("Estado: <fg=yellow>⚠️  PRÓXIMA A EXPIRAR ({$daysRemaining} días)</>");
                    $expiringCount++;
                    
                    if ($this->option('notify')) {
                        $this->warn("📧 Se debería enviar notificación al administrador.");
                    }
                } elseif ($daysRemaining <= 30) {
                    $this->line("Estado: <fg=yellow>⏰ Expira en {$daysRemaining} días</>");
                    $validCount++;
                } else {
                    $this->line("Estado: <fg=green>✓ VÁLIDA ({$daysRemaining} días restantes)</>");
                    $validCount++;
                }
            }
            
            $this->newLine();
        }

        // Resumen
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📊 RESUMEN:");
        $this->line("✓ Licencias válidas: <fg=green>{$validCount}</>");
        $this->line("⚠️  Próximas a expirar: <fg=yellow>{$expiringCount}</>");
        $this->line("✗ Licencias expiradas: <fg=red>{$expiredCount}</>");
        
        if ($expiredCount > 0 && !$this->option('deactivate')) {
            $this->newLine();
            $this->warn('💡 Tip: Use --deactivate para desactivar automáticamente las licencias expiradas');
        }

        if ($expiringCount > 0 && !$this->option('notify')) {
            $this->newLine();
            $this->warn('💡 Tip: Use --notify para enviar notificaciones sobre licencias próximas a expirar');
        }

        return Command::SUCCESS;
    }
}
