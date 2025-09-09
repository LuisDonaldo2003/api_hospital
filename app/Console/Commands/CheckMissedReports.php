<?php

namespace App\Console\Commands;

use App\Services\MissedReportRecoveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckMissedReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:check-missed {--force : Forzar verificación incluso si ya se ejecutó hoy}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica y envía reportes perdidos cuando el sistema se reinicia';

    protected $missedReportService;

    /**
     * Create a new command instance.
     */
    public function __construct(MissedReportRecoveryService $missedReportService)
    {
        parent::__construct();
        $this->missedReportService = $missedReportService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Verificando reportes perdidos...');
        
        try {
            $missedReports = $this->missedReportService->checkAndSendMissedReports();
            
            if (empty($missedReports)) {
                $this->info('✅ No hay reportes perdidos que recuperar');
                return 0;
            }
            
            $this->info("📧 Se recuperaron y enviaron " . count($missedReports) . " reporte(s) perdido(s):");
            
            foreach ($missedReports as $report) {
                if ($report['status'] === 'sent') {
                    $this->line("   ✅ {$report['formatted_date']} - {$report['activities_count']} actividades");
                } else {
                    $this->error("   ❌ {$report['formatted_date']} - Error: {$report['error']}");
                }
            }
            
            $this->newLine();
            $this->info('📨 Todos los reportes perdidos han sido enviados a: ' . config('mail.admin_email'));
            
            Log::info('Reportes perdidos recuperados exitosamente', [
                'total_recovered' => count($missedReports),
                'reports' => $missedReports
            ]);
            
        } catch (\Exception $e) {
            $this->error('❌ Error al verificar reportes perdidos: ' . $e->getMessage());
            Log::error('Error en verificación de reportes perdidos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        return 0;
    }
}
