<?php

namespace App\Http\Controllers\API;

use App\Models\AppointmentService;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AppointmentServiceController extends Controller
{
    /**
     * GET /api/appointment-services
     * Lista todos los servicios activos (Admin/Super Admin)
     */
    public function index()
    {
        $services = AppointmentService::active()
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $services
        ]);
    }

    /**
     * GET /api/appointment-services/accessible
     * Lista solo los servicios a los que el usuario autenticado tiene acceso
     */
    public function accessible(Request $request)
    {
        $user = $request->user();
        
        // Si el usuario no tiene servicios asignados, asumir que tiene acceso total
        // (caso de Super Admin o usuarios legacy)
        $userServices = $user->appointmentServices()->pluck('appointment_service_id')->toArray();
        
        if (empty($userServices)) {
            // Sin restricciones - devolver todos los servicios
            $services = AppointmentService::active()->ordered()->get();
        } else {
            // Con restricciones - solo servicios asignados
            $services = $user->appointmentServices()
                ->where('activo', true)
                ->orderBy('orden')
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => $services
        ]);
    }

    /**
     * GET /api/appointment-services/{id}
     * Obtener un servicio específico
     */
    public function show($id)
    {
        $service = AppointmentService::find($id);

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Servicio no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $service
        ]);
    }

    /**
     * POST /api/users/{userId}/assign-services
     * Asigna servicios a un usuario
     */
    public function assignServices(Request $request, $userId)
    {
        $request->validate([
            'service_ids' => 'required|array',
            'service_ids.*' => 'exists:appointment_services,id'
        ]);

        $user = User::findOrFail($userId);
        
        // Sincronizar servicios (elimina los no enviados, agrega los nuevos)
        $user->appointmentServices()->sync($request->service_ids);

        return response()->json([
            'success' => true,
            'message' => 'Servicios asignados correctamente'
        ]);
    }

    /**
     * GET /api/users/{userId}/services
     * Obtiene los servicios asignados a un usuario
     */
    public function userServices($userId)
    {
        $user = User::findOrFail($userId);
        $services = $user->appointmentServices()->get();

        return response()->json([
            'success' => true,
            'data' => $services
        ]);
    }

    /**
     * POST /api/appointment-services (Admin)
     * Crear un nuevo servicio
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:appointment_services,nombre',
            'descripcion' => 'nullable|string',
            'categoria' => 'nullable|string|max:50',
            'orden' => 'nullable|integer',
            'activo' => 'nullable|boolean'
        ]);

        $service = AppointmentService::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Servicio creado correctamente',
            'data' => $service
        ], 201);
    }

    /**
     * PUT /api/appointment-services/{id} (Admin)
     * Actualizar un servicio
     */
    public function update(Request $request, $id)
    {
        $service = AppointmentService::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:100|unique:appointment_services,nombre,' . $id,
            'descripcion' => 'nullable|string',
            'categoria' => 'nullable|string|max:50',
            'orden' => 'nullable|integer',
            'activo' => 'nullable|boolean'
        ]);

        $service->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Servicio actualizado correctamente',
            'data' => $service
        ]);
    }

    /**
     * DELETE /api/appointment-services/{id} (Admin)
     * Eliminar un servicio
     */
    public function destroy($id)
    {
        $service = AppointmentService::findOrFail($id);
        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Servicio eliminado correctamente'
        ]);
    }
}
