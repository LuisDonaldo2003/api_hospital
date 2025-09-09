<?php

namespace App\Console\Commands;

use App\Mail\DailyActivityReport;
use App\Services\ActivityReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendDailyActivityReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:daily-activity {--email= : Email específico para enviar el reporte} {--now : Enviar reporte del día actual en lugar del día anterior}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía un reporte diario de actividades del sistema por correo electrónico';

    /**
     * El servicio de reportes de actividad
     */
    protected $activityReportService;

    /**
     * Create a new command instance.
     */
    public function __construct(ActivityReportService $activityReportService)
    {
        parent::__construct();
        $this->activityReportService = $activityReportService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isCurrentDay = $this->option('now');
        $reportType = $isCurrentDay ? 'del momento actual' : 'diario';
        $isAutomated = !$this->option('email') && !$isCurrentDay; // Es automático si no se especifica email ni --now
        
        if ($isAutomated) {
            $this->info('🕛 Reporte automático programado - 12:00 AM Ciudad de México');
        }
        
        $this->info("🚀 Iniciando generación del reporte {$reportType} de actividades...");
        
        try {
            // Obtener el resumen de actividades según el tipo de reporte
            if ($isCurrentDay) {
                $reportData = $this->activityReportService->getCurrentDayActivitySummary();
                $this->info("📊 Reporte actual generado a las {$reportData['current_time']}");
            } else {
                $reportData = $this->activityReportService->getDailyActivitySummary();
            }
            
            if ($reportData['total_activities'] == 0) {
                $dateLabel = $isCurrentDay ? 'hoy' : 'el día anterior';
                $this->warn("⚠️  No se encontraron actividades para {$dateLabel}.");
                
                // Si es automático y no hay actividades, aún enviamos el reporte para confirmar que el sistema funciona
                if ($isAutomated) {
                    $this->info("📧 Enviando reporte de confirmación (sin actividades) para monitoreo...");
                    // Continuar con el envío del reporte vacío
                } else {
                    return;
                }
            }
            
            // Calcular porcentajes para actividades por tipo
            $reportData['activities_by_type'] = $this->activityReportService->calculatePercentages(
                $reportData['activities_by_type'],
                $reportData['total_activities']
            );
            
            // Agregar información del tipo de reporte
            $reportData['report_type'] = $isCurrentDay ? 'current' : 'daily';
            $reportData['report_label'] = $isCurrentDay ? 'Reporte del Momento Actual' : 'Reporte Diario';
            $reportData['is_automated'] = $isAutomated;
            
            if ($reportData['total_activities'] > 0) {
                $this->info("📊 Resumen generado: {$reportData['total_activities']} actividades encontradas");
            }
            
            // Determinar email de destino
            $recipientEmail = $this->option('email') ?? config('mail.admin_email', 'monsterpark1000@gmail.com');
            
            // Enviar el reporte por correo
            Mail::to($recipientEmail)->send(new DailyActivityReport($reportData));
            
            // Registrar el envío del reporte
            if (!$isCurrentDay) {
                $this->recordReportSent($reportData);
            }
            
            $this->info("✅ Reporte {$reportType} enviado exitosamente a: {$recipientEmail}");
            $this->info("📅 Fecha del reporte: {$reportData['formatted_date']}");
            
            if ($isAutomated) {
                $this->info("⏰ Próximo reporte automático: mañana a las 12:00 AM");
            }
            
            // Log del envío
            Log::info("Reporte {$reportType} de actividades enviado", [
                'recipient' => $recipientEmail,
                'date' => $reportData['date'],
                'total_activities' => $reportData['total_activities'],
                'active_users' => $reportData['active_users_count'],
                'report_type' => $reportData['report_type'],
                'is_automated' => $isAutomated,
                'timezone' => 'America/Mexico_City'
            ]);
            
            // Mostrar resumen en consola
            $this->displaySummary($reportData);
            
        } catch (\Exception $e) {
            $this->error("❌ Error al generar/enviar el reporte {$reportType}: " . $e->getMessage());
            Log::error("Error en reporte {$reportType} de actividades", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'is_automated' => $isAutomated ?? false
            ]);
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Muestra un resumen del reporte en la consola
     */
    private function displaySummary($reportData)
    {
        $this->newLine();
        $this->line('📋 <fg=cyan>RESUMEN DEL REPORTE DIARIO</fg=cyan>');
        $this->line('═══════════════════════════════════');
        $this->line("📅 Fecha: <fg=white>{$reportData['formatted_date']}</fg=white>");
        $this->line("🔢 Total actividades: <fg=green>{$reportData['total_activities']}</fg=green>");
        $this->line("👥 Usuarios activos: <fg=yellow>{$reportData['active_users_count']}</fg=yellow>");
        
        if ($reportData['hourly_stats']['peak_hour']) {
            $peakHour = $reportData['hourly_stats']['peak_hour'];
            $this->line("⏰ Hora pico: <fg=magenta>{$peakHour['formatted_hour']} ({$peakHour['count']} actividades)</fg=magenta>");
        }
        
        $trend = $reportData['period_comparison']['trend'];
        $trendIcon = $trend === 'up' ? '📈' : ($trend === 'down' ? '📉' : '➡️');
        $this->line("📊 Tendencia: {$trendIcon} <fg=white>{$reportData['period_comparison']['percentage_change']}%</fg=white>");
        
        $this->newLine();
    }
    
    /**
     * Registra que un reporte fue enviado
     */
    private function recordReportSent($reportData)
    {
        try {
            DB::table('sent_reports')->updateOrInsert(
                ['report_date' => $reportData['date']],
                [
                    'report_date' => $reportData['date'],
                    'sent_at' => now(),
                    'report_type' => $reportData['is_automated'] ?? false ? 'scheduled' : 'manual',
                    'total_activities' => $reportData['total_activities'],
                    'metadata' => json_encode([
                        'active_users' => $reportData['active_users_count'],
                        'peak_hour' => $reportData['hourly_stats']['peak_hour']['formatted_hour'] ?? null,
                        'trend' => $reportData['period_comparison']['trend'] ?? null
                    ]),
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        } catch (\Exception $e) {
            Log::warning('No se pudo registrar el envío del reporte', [
                'error' => $e->getMessage(),
                'report_date' => $reportData['date']
            ]);
        }
    }
}
