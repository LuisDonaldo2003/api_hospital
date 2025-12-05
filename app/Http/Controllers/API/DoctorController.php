<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Doctor;
use App\Models\Especialidad;
use App\Services\ActivityLoggerService;
use Illuminate\Support\Facades\Validator;

class DoctorController extends Controller
{
    /**
     * Listar doctores con filtros y paginación
     */
    public function index(Request $request)
    {
        $query = Doctor::with('especialidad');

        // Filtro por búsqueda
        if ($search = $request->get('search')) {
            $query->where('nombre_completo', 'like', "%{$search}%");
        }

        // Filtro por especialidad
        if ($especialidad = $request->get('especialidad_id')) {
            $query->where('especialidad_id', $especialidad);
        }

        // Filtro por turno
        if ($turno = $request->get('turno')) {
            $query->where('turno', $turno);
        }

        // Filtro por activo
        if ($request->has('activo')) {
            $query->where('activo', $request->get('activo'));
        }

        $doctors = $query->orderBy('nombre_completo')->get();

        // Registrar actividad de lectura
        ActivityLoggerService::logRead('Doctor', null, 'doctors', [
            'total_records' => $doctors->count()
        ]);

        return response()->json([
            'success' => true,
            'data' => $doctors,
            'total' => $doctors->count(),
        ]);
    }

    /**
     * Obtener un doctor específico
     */
    public function show($id)
    {
        $doctor = Doctor::with('especialidad')->find($id);
        
        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor no encontrado'
            ], 404);
        }

        // Registrar actividad de lectura
        ActivityLoggerService::logRead('Doctor', $doctor->id, 'doctors', [
            'nombre' => $doctor->nombre_completo
        ]);

        return response()->json([
            'success' => true,
            'data' => $doctor
        ]);
    }

    /**
     * Crear un nuevo doctor
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre_completo' => 'required|string|max:255',
            'especialidad_id' => 'required|exists:especialidades,id',
            'turno' => 'required|in:Matutino,Vespertino,Mixto',
            'hora_inicio_matutino' => 'nullable|date_format:H:i',
            'hora_fin_matutino' => 'nullable|date_format:H:i',
            'hora_inicio_vespertino' => 'nullable|date_format:H:i',
            'hora_fin_vespertino' => 'nullable|date_format:H:i',
            'activo' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $doctor = Doctor::create($request->all());

        // Registrar actividad de creación
        ActivityLoggerService::logCreate('Doctor', $doctor->id, 'doctors', [
            'nombre' => $doctor->nombre_completo,
            'especialidad' => $doctor->especialidad_nombre
        ]);

        return response()->json([
            'success' => true,
            'data' => $doctor->load('especialidad'),
            'message' => 'Doctor creado exitosamente'
        ], 201);
    }

    /**
     * Actualizar un doctor
     */
    public function update(Request $request, $id)
    {
        $doctor = Doctor::find($id);
        
        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre_completo' => 'required|string|max:255',
            'especialidad_id' => 'required|exists:especialidades,id',
            'turno' => 'required|in:Matutino,Vespertino,Mixto',
            'hora_inicio_matutino' => 'nullable|date_format:H:i',
            'hora_fin_matutino' => 'nullable|date_format:H:i',
            'hora_inicio_vespertino' => 'nullable|date_format:H:i',
            'hora_fin_vespertino' => 'nullable|date_format:H:i',
            'activo' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Guardar valores antiguos para el log
        $oldValues = [
            'nombre' => $doctor->nombre_completo,
            'especialidad' => $doctor->especialidad_nombre
        ];

        $doctor->update($request->all());

        // Registrar actividad de actualización
        $newValues = [
            'nombre' => $doctor->nombre_completo,
            'especialidad' => $doctor->especialidad_nombre
        ];
        ActivityLoggerService::logUpdate('Doctor', $doctor->id, 'doctors', $oldValues, $newValues);

        return response()->json([
            'success' => true,
            'data' => $doctor->load('especialidad'),
            'message' => 'Doctor actualizado exitosamente'
        ]);
    }

    /**
     * Eliminar un doctor
     */
    public function destroy($id)
    {
        $doctor = Doctor::find($id);
        
        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor no encontrado'
            ], 404);
        }

        // Verificar si tiene citas pendientes
        $citasPendientes = $doctor->citas()
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->count();

        if ($citasPendientes > 0) {
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar el doctor porque tiene {$citasPendientes} cita(s) pendiente(s)"
            ], 400);
        }

        // Registrar actividad de eliminación
        ActivityLoggerService::logDelete('Doctor', $doctor->id, 'doctors', [
            'nombre' => $doctor->nombre_completo,
            'especialidad' => $doctor->especialidad_nombre
        ]);

        $doctor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Doctor eliminado exitosamente'
        ]);
    }

    /**
     * Obtener doctores por especialidad
     */
    public function getByEspecialidad($especialidadId)
    {
        $doctors = Doctor::with('especialidad')
            ->where('especialidad_id', $especialidadId)
            ->where('activo', true)
            ->orderBy('nombre_completo')
            ->get();

        // Registrar actividad de lectura
        ActivityLoggerService::logRead('Doctor', null, 'doctors', [
            'filter' => 'by_especialidad',
            'especialidad_id' => $especialidadId,
            'total_records' => $doctors->count()
        ]);

        return response()->json([
            'success' => true,
            'data' => $doctors,
            'total' => $doctors->count(),
            'message' => $doctors->count() > 0 
                ? 'Doctores encontrados' 
                : 'No hay doctores disponibles para esta especialidad'
        ]);
    }

    /**
     * Listar especialidades
     */
    public function listEspecialidades()
    {
        $especialidades = Especialidad::where('activo', true)
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $especialidades
        ]);
    }

    /**
     * Obtener estadísticas de doctores
     */
    public function stats()
    {
        $total = Doctor::count();
        $activos = Doctor::where('activo', true)->count();
        $porEspecialidad = Doctor::with('especialidad')
            ->select('especialidad_id', \DB::raw('count(*) as total'))
            ->groupBy('especialidad_id')
            ->get()
            ->map(function ($item) {
                return [
                    'especialidad' => $item->especialidad->nombre ?? 'Sin especialidad',
                    'total' => $item->total
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'activos' => $activos,
                'inactivos' => $total - $activos,
                'por_especialidad' => $porEspecialidad
            ]
        ]);
    }
}
