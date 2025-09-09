<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ActivityReportService
{
    /**
     * Obtiene el resumen de actividades del día anterior
     *
     * @return array
     */
    public function getDailyActivitySummary()
    {
        $yesterday = Carbon::yesterday();
        
        // Obtener total de actividades
        $totalActivities = $this->getTotalActivities($yesterday);
        
        // Obtener actividades por tipo
        $activitiesByType = $this->getActivitiesByType($yesterday);
        
        // Obtener usuarios activos
        $activeUsers = $this->getActiveUsers($yesterday);
        
        // Obtener estadísticas por hora
        $hourlyStats = $this->getHourlyStats($yesterday);
        
        // Top usuarios más activos
        $topUsers = $this->getTopActiveUsers($yesterday);
        
        return [
            'date' => $yesterday->format('Y-m-d'),
            'formatted_date' => $yesterday->format('d/m/Y'),
            'total_activities' => $totalActivities,
            'activities_by_type' => $activitiesByType,
            'active_users_count' => $activeUsers['count'],
            'active_users_list' => $activeUsers['users'],
            'hourly_stats' => $hourlyStats,
            'top_users' => $topUsers,
            'period_comparison' => $this->getPeriodComparison($yesterday)
        ];
    }

    /**
     * Obtiene el resumen de actividades del día actual (momento presente)
     *
     * @return array
     */
    public function getCurrentDayActivitySummary()
    {
        $today = Carbon::today();
        
        // Obtener total de actividades
        $totalActivities = $this->getTotalActivities($today);
        
        // Obtener actividades por tipo
        $activitiesByType = $this->getActivitiesByType($today);
        
        // Obtener usuarios activos
        $activeUsers = $this->getActiveUsers($today);
        
        // Obtener estadísticas por hora
        $hourlyStats = $this->getHourlyStats($today);
        
        // Top usuarios más activos
        $topUsers = $this->getTopActiveUsers($today);
        
        return [
            'date' => $today->format('Y-m-d'),
            'formatted_date' => $today->format('d/m/Y'),
            'current_time' => Carbon::now()->format('H:i:s'),
            'total_activities' => $totalActivities,
            'activities_by_type' => $activitiesByType,
            'active_users_count' => $activeUsers['count'],
            'active_users_list' => $activeUsers['users'],
            'hourly_stats' => $hourlyStats,
            'top_users' => $topUsers,
            'period_comparison' => $this->getPeriodComparison($today)
        ];
    }
    
    /**
     * Obtiene el total de actividades del día
     */
    private function getTotalActivities($date)
    {
        return DB::table('user_activities')
            ->whereDate('created_at', $date)
            ->count();
    }
    
    /**
     * Obtiene actividades agrupadas por tipo
     */
    private function getActivitiesByType($date)
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
                    'percentage' => 0 // Se calculará después
                ];
            });
    }
    
    /**
     * Obtiene usuarios activos
     */
    private function getActiveUsers($date)
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
            'users' => $users->take(10) // Top 10 usuarios
        ];
    }
    
    /**
     * Obtiene estadísticas por hora
     */
    private function getHourlyStats($date)
    {
        $hourlyData = DB::table('user_activities')
            ->select(DB::raw('EXTRACT(HOUR FROM created_at) as hour'), DB::raw('count(*) as count'))
            ->whereDate('created_at', $date)
            ->groupBy(DB::raw('EXTRACT(HOUR FROM created_at)'))
            ->orderBy('hour')
            ->get();
            
        // Crear array de 24 horas
        $hours = [];
        for ($i = 0; $i < 24; $i++) {
            $hours[$i] = [
                'hour' => $i,
                'formatted_hour' => sprintf('%02d:00', $i),
                'count' => 0,
                'period' => $this->getTimePeriod($i)
            ];
        }
        
        // Llenar con datos reales
        foreach ($hourlyData as $data) {
            $hours[(int)$data->hour]['count'] = $data->count;
        }
        
        // Encontrar hora pico
        $peakHour = collect($hours)->sortByDesc('count')->first();
        
        return [
            'hours' => array_values($hours),
            'peak_hour' => $peakHour,
            'total_hours_active' => collect($hours)->where('count', '>', 0)->count()
        ];
    }
    
    /**
     * Obtiene los usuarios más activos
     */
    private function getTopActiveUsers($date, $limit = 5)
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
    
    /**
     * Obtiene comparación con período anterior
     */
    private function getPeriodComparison($date)
    {
        $previousDay = $date->copy()->subDay();
        
        $currentTotal = $this->getTotalActivities($date);
        $previousTotal = $this->getTotalActivities($previousDay);
        
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
    
    /**
     * Determina el período del día según la hora
     */
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
    
    /**
     * Calcula porcentajes para actividades por tipo
     */
    public function calculatePercentages($activities, $total)
    {
        return $activities->map(function ($activity) use ($total) {
            $activity['percentage'] = $total > 0 ? round(($activity['count'] / $total) * 100, 2) : 0;
            return $activity;
        });
    }
}
