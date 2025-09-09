<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\DailyActivityReport;

class MissedReportRecoveryService
{
    protected $activityReportService;

    public function __construct(ActivityReportService $activityReportService)
    {
        $this->activityReportService = $activityReportService;
    }

    /**
     * Detecta y envía reportes perdidos desde la última ejecución
     */
    public function checkAndSendMissedReports()
    {
        $lastReportDate = $this->getLastReportDate();
        $today = Carbon::now()->timezone('America/Mexico_City');
        $missedDates = $this->getMissedReportDates($lastReportDate, $today);

        if (empty($missedDates)) {
            Log::info('No hay reportes perdidos que recuperar');
            return [];
        }

        $sentReports = [];
        foreach ($missedDates as $missedDate) {
            try {
                $reportData = $this->generateReportForDate($missedDate);
                $this->sendMissedReport($reportData);
                $this->recordReportSent($missedDate);
                
                $sentReports[] = [
                    'date' => $missedDate->format('Y-m-d'),
                    'formatted_date' => $missedDate->format('d/m/Y'),
                    'activities_count' => $reportData['total_activities'],
                    'status' => 'sent'
                ];

                Log::info("Reporte perdido recuperado y enviado", [
                    'date' => $missedDate->format('Y-m-d'),
                    'total_activities' => $reportData['total_activities']
                ]);

            } catch (\Exception $e) {
                Log::error("Error al enviar reporte perdido", [
                    'date' => $missedDate->format('Y-m-d'),
                    'error' => $e->getMessage()
                ]);
                
                $sentReports[] = [
                    'date' => $missedDate->format('Y-m-d'),
                    'formatted_date' => $missedDate->format('d/m/Y'),
                    'activities_count' => 0,
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }

        return $sentReports;
    }

    /**
     * Obtiene la fecha del último reporte enviado
     */
    private function getLastReportDate()
    {
        $lastReport = DB::table('sent_reports')
            ->orderBy('report_date', 'desc')
            ->first();

        if ($lastReport) {
            return Carbon::parse($lastReport->report_date);
        }

        // Si no hay registros, asumir que el último reporte fue hace 2 días
        return Carbon::now()->timezone('America/Mexico_City')->subDays(2);
    }

    /**
     * Obtiene las fechas de reportes perdidos
     */
    private function getMissedReportDates($lastReportDate, $today)
    {
        $missedDates = [];
        $checkDate = $lastReportDate->copy()->addDay();

        // Verificar desde el día siguiente al último reporte hasta ayer
        while ($checkDate->format('Y-m-d') < $today->format('Y-m-d')) {
            // Solo agregar días laborales si es necesario, o todos los días
            $missedDates[] = $checkDate->copy();
            $checkDate->addDay();
        }

        return $missedDates;
    }

    /**
     * Genera reporte para una fecha específica
     */
    private function generateReportForDate($date)
    {
        // Modificar temporalmente el método del servicio para usar fecha específica
        $totalActivities = $this->getTotalActivitiesForDate($date);
        $activitiesByType = $this->getActivitiesByTypeForDate($date);
        $activeUsers = $this->getActiveUsersForDate($date);
        $hourlyStats = $this->getHourlyStatsForDate($date);
        $topUsers = $this->getTopActiveUsersForDate($date);

        $reportData = [
            'date' => $date->format('Y-m-d'),
            'formatted_date' => $date->format('d/m/Y'),
            'total_activities' => $totalActivities,
            'activities_by_type' => $this->activityReportService->calculatePercentages(
                $activitiesByType,
                $totalActivities
            ),
            'active_users_count' => $activeUsers['count'],
            'active_users_list' => $activeUsers['users'],
            'hourly_stats' => $hourlyStats,
            'top_users' => $topUsers,
            'period_comparison' => $this->getPeriodComparisonForDate($date),
            'is_missed_report' => true,
            'report_type' => 'daily',
            'report_label' => 'Reporte Diario Recuperado'
        ];

        return $reportData;
    }

    /**
     * Envía un reporte perdido por email
     */
    private function sendMissedReport($reportData)
    {
        $recipientEmail = config('mail.admin_email', 'monsterpark1000@gmail.com');
        Mail::to($recipientEmail)->send(new DailyActivityReport($reportData));
    }

    /**
     * Registra que un reporte fue enviado
     */
    private function recordReportSent($date)
    {
        DB::table('sent_reports')->updateOrInsert(
            ['report_date' => $date->format('Y-m-d')],
            [
                'report_date' => $date->format('Y-m-d'),
                'sent_at' => now(),
                'report_type' => 'recovered',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );
    }

    // Métodos auxiliares para obtener datos de fechas específicas
    private function getTotalActivitiesForDate($date)
    {
        return DB::table('user_activities')
            ->whereDate('created_at', $date)
            ->count();
    }

    private function getActivitiesByTypeForDate($date)
    {
        return DB::table('user_activities')
            ->select('action_type', DB::raw('count(*) as count'))
            ->whereDate('created_at', $date)
            ->groupBy('action_type')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->action_type ?? 'Sin clasificar',
                    'count' => $item->count,
                    'percentage' => 0
                ];
            });
    }

    private function getActiveUsersForDate($date)
    {
        $users = DB::table('user_activities')
            ->join('users', 'user_activities.user_id', '=', 'users.id')
            ->select('users.id', 'users.name', 'users.email', DB::raw('count(*) as activity_count'))
            ->whereDate('user_activities.created_at', $date)
            ->whereNotNull('user_activities.user_id')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderBy('activity_count', 'desc')
            ->get();

        return [
            'count' => $users->count(),
            'users' => $users->take(10)
        ];
    }

    private function getHourlyStatsForDate($date)
    {
        $hourlyData = DB::table('user_activities')
            ->select(DB::raw('EXTRACT(HOUR FROM created_at) as hour'), DB::raw('count(*) as count'))
            ->whereDate('created_at', $date)
            ->groupBy(DB::raw('EXTRACT(HOUR FROM created_at)'))
            ->orderBy('hour')
            ->get();

        $hours = [];
        for ($i = 0; $i < 24; $i++) {
            $hours[$i] = [
                'hour' => $i,
                'formatted_hour' => sprintf('%02d:00', $i),
                'count' => 0,
                'period' => $this->getTimePeriod($i)
            ];
        }

        foreach ($hourlyData as $data) {
            $hours[(int)$data->hour]['count'] = $data->count;
        }

        $peakHour = collect($hours)->sortByDesc('count')->first();

        return [
            'hours' => array_values($hours),
            'peak_hour' => $peakHour,
            'total_hours_active' => collect($hours)->where('count', '>', 0)->count()
        ];
    }

    private function getTopActiveUsersForDate($date, $limit = 5)
    {
        return DB::table('user_activities')
            ->join('users', 'user_activities.user_id', '=', 'users.id')
            ->select('users.name', 'users.email', DB::raw('count(*) as activity_count'))
            ->whereDate('user_activities.created_at', $date)
            ->whereNotNull('user_activities.user_id')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderBy('activity_count', 'desc')
            ->limit($limit)
            ->get();
    }

    private function getPeriodComparisonForDate($date)
    {
        $previousDay = $date->copy()->subDay();
        
        $currentTotal = $this->getTotalActivitiesForDate($date);
        $previousTotal = $this->getTotalActivitiesForDate($previousDay);
        
        $difference = $currentTotal - $previousTotal;
        $percentage = $previousTotal > 0 ? (($difference / $previousTotal) * 100) : 0;
        
        return [
            'current_total' => $currentTotal,
            'previous_total' => $previousTotal,
            'difference' => $difference,
            'percentage_change' => round($percentage, 2),
            'trend' => $difference > 0 ? 'up' : ($difference < 0 ? 'down' : 'stable')
        ];
    }

    private function getTimePeriod($hour)
    {
        if ($hour >= 6 && $hour < 12) {
            return 'morning';
        } elseif ($hour >= 12 && $hour < 18) {
            return 'afternoon';
        } elseif ($hour >= 18 && $hour < 24) {
            return 'evening';
        } else {
            return 'night';
        }
    }
}
