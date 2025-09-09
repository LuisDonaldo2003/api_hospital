<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * Servicio para registrar actividades de usuarios de forma automática
 * 
 * Este servicio simplifica el registro de actividades proporcionando
 * métodos específicos para cada tipo de acción.
 */
class ActivityLoggerService
{
    /**
     * Registra una actividad de creación
     *
     * @param string $module
     * @param int|null $recordId
     * @param string|null $table
     * @param array|null $newValues
     * @return void
     */
    public static function logCreate($module, $recordId = null, $table = null, $newValues = null)
    {
        $description = "Creó un nuevo registro en {$module}";
        self::logActivity('create', $module, $description, $table, $recordId, null, $newValues);
    }

    /**
     * Registra una actividad de lectura/visualización
     *
     * @param string $module
     * @param int|null $recordId
     * @param string|null $table
     * @param array|null $data
     * @return void
     */
    public static function logRead($module, $recordId = null, $table = null, $data = null)
    {
        $description = $recordId ? "Consultó un registro en {$module}" : "Consultó listado de {$module}";
        self::logActivity('read', $module, $description, $table, $recordId, null, $data);
    }

    /**
     * Registra una actividad de actualización
     *
     * @param string $module
     * @param int|null $recordId
     * @param string|null $table
     * @param array|null $oldValues
     * @param array|null $newValues
     * @return void
     */
    public static function logUpdate($module, $recordId = null, $table = null, $oldValues = null, $newValues = null)
    {
        $description = "Actualizó un registro en {$module}";
        self::logActivity('update', $module, $description, $table, $recordId, $oldValues, $newValues);
    }

    /**
     * Registra una actividad de eliminación
     *
     * @param string $module
     * @param int|null $recordId
     * @param string|null $table
     * @param array|null $oldValues
     * @return void
     */
    public static function logDelete($module, $recordId = null, $table = null, $oldValues = null)
    {
        $description = "Eliminó un registro en {$module}";
        self::logActivity('delete', $module, $description, $table, $recordId, $oldValues);
    }

    /**
     * Registra una actividad de inicio de sesión
     *
     * @param string $description
     * @return void
     */
    public static function logLogin($description = 'Usuario inició sesión')
    {
        self::logActivity('login', 'auth', $description);
    }

    /**
     * Registra una actividad de cierre de sesión
     *
     * @param string $description
     * @return void
     */
    public static function logLogout($description = 'Usuario cerró sesión')
    {
        self::logActivity('logout', 'auth', $description);
    }

    /**
     * Registra una actividad de exportación
     *
     * @param string $module
     * @param string $description
     * @param string|null $table
     * @return void
     */
    public static function logExport($module, $description, $table = null)
    {
        self::logActivity('export', $module, $description, $table);
    }

    /**
     * Registra una actividad de importación
     *
     * @param string $module
     * @param string $description
     * @param string|null $table
     * @param array|null $newValues
     * @return void
     */
    public static function logImport($module, $description, $table = null, $newValues = null)
    {
        self::logActivity('import', $module, $description, $table, null, null, $newValues);
    }

    /**
     * Registra una actividad genérica
     *
     * @param string $actionType
     * @param string $module
     * @param string $description
     * @param string|null $table
     * @param int|null $recordId
     * @param array|null $oldValues
     * @param array|null $newValues
     * @return void
     */
    public static function logActivity(
        $actionType, 
        $module, 
        $description, 
        $table = null, 
        $recordId = null, 
        $oldValues = null, 
        $newValues = null
    ) {
        try {
            // Verificar si hay un usuario autenticado
            $userId = Auth::check() ? Auth::id() : 1; // Usar ID 1 como fallback para debug
            
            if (!Auth::check()) {
                // Log para debug
                \Log::warning('ActivityLoggerService: Usuario no autenticado, usando ID 1 como fallback');
            }

            $data = [
                'user_id' => $userId,
                'action_type' => $actionType,
                'module' => $module,
                'description' => $description,
                'affected_table' => $table,
                'affected_record_id' => $recordId,
                'old_values' => $oldValues ? json_encode($oldValues) : null,
                'new_values' => $newValues ? json_encode($newValues) : null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];

            DB::table('user_activities')->insert($data);
            
            // Log para debug
            \Log::info('ActivityLoggerService: Actividad registrada correctamente', $data);

        } catch (\Exception $e) {
            // Log the error but don't break the main functionality
            \Log::error('Error logging user activity: ' . $e->getMessage());
        }
    }

    /**
     * Registra múltiples actividades en lote
     *
     * @param array $activities
     * @return void
     */
    public static function logBatchActivities($activities)
    {
        try {
            if (!Auth::check() || empty($activities)) {
                return;
            }

            $batchData = [];
            $now = Carbon::now();

            foreach ($activities as $activity) {
                $batchData[] = [
                    'user_id' => Auth::id(),
                    'action_type' => $activity['action_type'],
                    'module' => $activity['module'],
                    'description' => $activity['description'],
                    'affected_table' => $activity['table'] ?? null,
                    'affected_record_id' => $activity['record_id'] ?? null,
                    'old_values' => isset($activity['old_values']) ? json_encode($activity['old_values']) : null,
                    'new_values' => isset($activity['new_values']) ? json_encode($activity['new_values']) : null,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }

            DB::table('user_activities')->insert($batchData);

        } catch (\Exception $e) {
            \Log::error('Error logging batch activities: ' . $e->getMessage());
        }
    }

    /**
     * Limpia actividades antiguas (por ejemplo, mayores a 6 meses)
     *
     * @param int $months
     * @return int Número de registros eliminados
     */
    public static function cleanOldActivities($months = 6)
    {
        try {
            $cutoffDate = Carbon::now()->subMonths($months);
            
            return DB::table('user_activities')
                ->where('created_at', '<', $cutoffDate)
                ->delete();

        } catch (\Exception $e) {
            \Log::error('Error cleaning old activities: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene un resumen de actividades para un usuario específico
     *
     * @param int $userId
     * @param int $days Días atrás para el resumen
     * @return array
     */
    public static function getUserActivitySummary($userId, $days = 30)
    {
        try {
            $startDate = Carbon::now()->subDays($days);

            return [
                'total_activities' => DB::table('user_activities')
                    ->where('user_id', $userId)
                    ->where('created_at', '>=', $startDate)
                    ->count(),
                'by_action' => DB::table('user_activities')
                    ->select('action_type', DB::raw('COUNT(*) as count'))
                    ->where('user_id', $userId)
                    ->where('created_at', '>=', $startDate)
                    ->groupBy('action_type')
                    ->get()->toArray(),
                'by_module' => DB::table('user_activities')
                    ->select('module', DB::raw('COUNT(*) as count'))
                    ->where('user_id', $userId)
                    ->where('created_at', '>=', $startDate)
                    ->groupBy('module')
                    ->get()->toArray(),
                'last_activity' => DB::table('user_activities')
                    ->where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->first()
            ];

        } catch (\Exception $e) {
            \Log::error('Error getting user activity summary: ' . $e->getMessage());
            return [];
        }
    }
}
