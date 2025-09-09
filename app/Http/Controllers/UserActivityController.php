<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * Controlador para gestionar el historial de actividades de usuarios
 * 
 * Este controlador proporciona endpoints para:
 * - Obtener estadísticas del dashboard de actividades
 * - Listar actividades recientes con filtros
 * - Obtener estadísticas por usuario, módulo y tipo de acción
 * - Registrar nuevas actividades
 */
class UserActivityController extends Controller
{
    /**
     * Obtiene las estadísticas principales para el dashboard
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        try {
            $today = Carbon::today();
            $startOfWeek = Carbon::now()->startOfWeek();
            $startOfMonth = Carbon::now()->startOfMonth();

            // Estadísticas principales
            $stats = [
                'todayActivities' => DB::table('user_activities')
                    ->whereDate('created_at', $today)
                    ->count(),
                'weekActivities' => DB::table('user_activities')
                    ->where('created_at', '>=', $startOfWeek)
                    ->count(),
                'monthActivities' => DB::table('user_activities')
                    ->where('created_at', '>=', $startOfMonth)
                    ->count(),
                'totalActivities' => DB::table('user_activities')->count(),
                'activeUsers' => DB::table('user_activities')
                    ->where('created_at', '>=', $startOfWeek)
                    ->distinct('user_id')
                    ->count('user_id'),
                'mostActiveUser' => DB::table('user_activities')
                    ->select('users.name')
                    ->join('users', 'user_activities.user_id', '=', 'users.id')
                    ->where('user_activities.created_at', '>=', $startOfWeek)
                    ->groupBy('user_activities.user_id', 'users.name')
                    ->orderByRaw('COUNT(*) DESC')
                    ->limit(1)
                    ->value('name') ?? 'N/A'
            ];

            // Datos para gráfico diario (últimos 7 días)
            $dailySeries = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $count = DB::table('user_activities')
                    ->whereDate('created_at', $date)
                    ->count();
                
                $dailySeries[] = [
                    'date' => $date->toDateString(),
                    'count' => $count
                ];
            }

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'dailySeries' => $dailySeries
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene la lista de actividades recientes con filtros
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecentActivities(Request $request)
    {
        try {
            $query = DB::table('user_activities')
                ->select([
                    'user_activities.*',
                    'users.name as user_name',
                    'roles.name as role_name'
                ])
                ->join('users', 'user_activities.user_id', '=', 'users.id')
                ->leftJoin('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->orderBy('user_activities.created_at', 'desc');

            // Aplicar filtros
            if ($request->filled('user_id')) {
                $query->where('user_activities.user_id', $request->user_id);
            }

            if ($request->filled('action_type')) {
                $query->where('user_activities.action_type', $request->action_type);
            }

            if ($request->filled('module')) {
                $query->where('user_activities.module', $request->module);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('user_activities.created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('user_activities.created_at', '<=', $request->date_to);
            }

            if ($request->filled('search')) {
                $query->where('user_activities.description', 'like', '%' . $request->search . '%');
            }

            // Paginación
            $skip = $request->get('skip', 0);
            $limit = $request->get('limit', 50);
            
            $activities = $query->skip($skip)->take($limit)->get();
            $total = $query->count();

            return response()->json([
                'success' => true,
                'activities' => $activities,
                'total' => $total
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener actividades: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene estadísticas de actividades por usuario
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActivitiesByUser()
    {
        try {
            $data = DB::table('user_activities')
                ->select([
                    'users.name as user_name',
                    'roles.name as role',
                    DB::raw('COUNT(*) as count')
                ])
                ->join('users', 'user_activities.user_id', '=', 'users.id')
                ->leftJoin('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->groupBy('users.id', 'users.name', 'roles.name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas por usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene estadísticas de actividades por módulo
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActivitiesByModule()
    {
        try {
            $data = DB::table('user_activities')
                ->select([
                    'module',
                    DB::raw('COUNT(*) as count')
                ])
                ->groupBy('module')
                ->orderBy('count', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas por módulo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene estadísticas de actividades por tipo de acción
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActivitiesByActionType()
    {
        try {
            $data = DB::table('user_activities')
                ->select([
                    'action_type',
                    DB::raw('COUNT(*) as count')
                ])
                ->groupBy('action_type')
                ->orderBy('count', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas por tipo de acción: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene los usuarios más activos
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMostActiveUsers(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);

            $data = DB::table('user_activities')
                ->select([
                    'users.name as user_name',
                    'roles.name as role',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('MAX(user_activities.created_at) as last_activity')
                ])
                ->join('users', 'user_activities.user_id', '=', 'users.id')
                ->leftJoin('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('user_activities.created_at', '>=', Carbon::now()->subWeek())
                ->groupBy('users.id', 'users.name', 'roles.name')
                ->orderBy('count', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios más activos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registra una nueva actividad de usuario
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logActivity(Request $request)
    {
        try {
            $validated = $request->validate([
                'action_type' => 'required|string|max:50',
                'module' => 'required|string|max:50',
                'description' => 'required|string|max:500',
                'affected_table' => 'nullable|string|max:100',
                'affected_record_id' => 'nullable|integer',
                'old_values' => 'nullable|json',
                'new_values' => 'nullable|json',
                'ip_address' => 'nullable|ip',
                'user_agent' => 'nullable|string'
            ]);

            $validated['user_id'] = Auth::id();
            $validated['ip_address'] = $request->ip();
            $validated['user_agent'] = $request->userAgent();

            $activityId = DB::table('user_activities')->insertGetId($validated);

            return response()->json([
                'success' => true,
                'message' => 'Actividad registrada correctamente',
                'activity_id' => $activityId
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar actividad: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene el detalle de una actividad específica
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActivityDetail($id)
    {
        try {
            $activity = DB::table('user_activities')
                ->select([
                    'user_activities.*',
                    'users.name as user_name',
                    'users.email as user_email',
                    'roles.name as role_name'
                ])
                ->join('users', 'user_activities.user_id', '=', 'users.id')
                ->leftJoin('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('user_activities.id', $id)
                ->first();

            if (!$activity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Actividad no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'activity' => $activity
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalle de actividad: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene las actividades de un usuario específico
     *
     * @param int $userId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserActivities($userId, Request $request)
    {
        try {
            $skip = $request->get('skip', 0);
            $limit = $request->get('limit', 50);

            $activities = DB::table('user_activities')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->skip($skip)
                ->take($limit)
                ->get();

            $total = DB::table('user_activities')
                ->where('user_id', $userId)
                ->count();

            return response()->json([
                'success' => true,
                'activities' => $activities,
                'total' => $total
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener actividades del usuario: ' . $e->getMessage()
            ], 500);
        }
    }
}
