<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cita;
use App\Models\Doctor;
use App\Services\ActivityLoggerService;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CitaController extends Controller
{
    /**
     * Listar citas con filtros
     */
    public function index(Request $request)
    {
        $query = Cita::with(['doctorRelation.especialidad', 'pacienteRelation.person']);
        $user = auth()->user();

        // -------------------------------------------------------------
        // FILTRADO ROBUSTO POR ROL/PERMISOS
        // -------------------------------------------------------------
        // Si tiene uno pero NO el otro, filtramos estrictamente.
        // Si tiene ambos o es superadmin (tiene todos), no se aplica filtro extra aquí.
        
        $canSpecialist = $user->can('schedule_specialist_appointment');
        $canGeneral = $user->can('schedule_general_appointment');

        // Caso: Solo puede ver Especialistas
        if ($canSpecialist && !$canGeneral) {
            $query->whereHas('doctorRelation', function($q) {
                $q->whereNotNull('especialidad_id');
            });
        }
        // Caso: Solo puede ver Generales
        elseif ($canGeneral && !$canSpecialist) {
            $query->whereHas('doctorRelation', function($q) {
                $q->whereNotNull('general_medical_id');
            });
        }
        // Si no tiene ninguno de los dos permisos específicos (pero entró aquí por otros permisos), 
        // y NO es admin/director, podríamos bloquear, pero asumiremos que si tiene 'list_appointment' ve todo 
        // salvo que tenga la restricción específica de arriba.

        // Filtro por búsqueda
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('paciente_nombre', 'like', "%{$search}%")
                  ->orWhere('folio_expediente', 'like', "%{$search}%")
                  ->orWhere('numero_cel', 'like', "%{$search}%")
                  ->orWhere('procedencia', 'like', "%{$search}%")
                  ->orWhere('motivo', 'like', "%{$search}%")
                  ->orWhereHas('doctorRelation', function ($q2) use ($search) {
                      $q2->where('nombre', 'like', "%{$search}%")
                         ->orWhere('apellido_paterno', 'like', "%{$search}%");
                  });
            });
        }

        // Filtro por doctor
        if ($doctor = $request->get('doctor_id')) {
            $query->where('doctor_id', $doctor);
        }

        // Filtro por estado
        if ($estado = $request->get('estado')) {
            $query->where('estado', $estado);
        }

        // Filtro por rango de fechas
        if ($fechaInicio = $request->get('fecha_inicio')) {
            $query->whereDate('fecha', '>=', $fechaInicio);
        }

        if ($fechaFin = $request->get('fecha_fin')) {
            $query->whereDate('fecha', '<=', $fechaFin);
        }

        // Si no hay filtro de fecha, mostrar del mes actual
        if (!$request->has('fecha_inicio') && !$request->has('fecha_fin')) {
            $query->whereMonth('fecha', now()->month)
                  ->whereYear('fecha', now()->year);
        }

        // Ordenar por fecha y hora ascendente (las más próximas primero)
        $citas = $query->orderBy('fecha', 'asc')
                       ->orderBy('hora', 'asc')
                       ->get();

        // Registrar actividad de lectura
        ActivityLoggerService::logRead('Cita', null, 'citas', [
            'total_records' => $citas->count()
        ]);

        return response()->json([
            'success' => true,
            'data' => $citas,
            'total' => $citas->count(),
        ]);
    }

    /**
     * Obtener una cita específica
     */
    public function show($id)
    {
        $cita = Cita::with(['doctorRelation.especialidad', 'pacienteRelation.person'])
            ->find($id);
        
        if (!$cita) {
            return response()->json([
                'success' => false,
                'message' => 'Cita no encontrada'
            ], 404);
        }

        // VERIFICACIÓN DE PERMISOS
        $this->authorizeAppointmentAction($cita);

        // Registrar actividad de lectura
        ActivityLoggerService::logRead('Cita', $cita->id, 'citas', [
            'doctor' => $cita->doctor['nombre'] ?? 'N/A',
            'fecha' => $cita->fecha->format('Y-m-d')
        ]);

        return response()->json([
            'success' => true,
            'data' => $cita
        ]);
    }

    /**
     * Crear una nueva cita
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'paciente_id' => 'nullable|exists:patients,id',
            'folio_expediente' => 'required|string|max:100',
            'paciente_nombre' => 'required|string|max:200',
            'fecha_nacimiento' => 'required|date|before_or_equal:today',
            'numero_cel' => 'required|string|max:20',
            'procedencia' => 'required|string|max:200',
            'tipo_cita' => 'required|in:Primera vez,Subsecuente',
            'turno' => 'required|in:Matutino,Vespertino',
            'paciente_telefono' => 'nullable|string|max:20',
            'paciente_email' => 'nullable|email|max:100',
            'doctor_id' => 'required|exists:doctors,id',
            'fecha' => 'required|date',
            'hora' => 'required|date_format:H:i',
            'motivo' => 'required|string',
            'observaciones' => 'nullable|string',
            'estado' => 'nullable|in:pendiente,confirmada,en_progreso,completada,cancelada,no_asistio',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // VERIFICACIÓN DE PERMISOS DE CREACIÓN
        $doctor = Doctor::find($request->doctor_id);
        $this->authorizeDoctorSelection($doctor);


        // Verificar disponibilidad del doctor
        $conflicto = Cita::where('doctor_id', $request->doctor_id)
            ->where('fecha', $request->fecha)
            ->where('hora', $request->hora)
            ->whereNotIn('estado', ['cancelada', 'no_asistio'])
            ->exists();

        if ($conflicto) {
            return response()->json([
                'success' => false,
                'message' => 'El doctor ya tiene una cita agendada en ese horario'
            ], 400);
        }

        $data = $request->all();
        if (!isset($data['estado'])) {
            $data['estado'] = 'pendiente';
        }

        $cita = Cita::create($data);

        // Registrar actividad de creación
        ActivityLoggerService::logCreate('Cita', $cita->id, 'citas', [
            'doctor' => $cita->doctor['nombre'] ?? 'N/A',
            'fecha' => $cita->fecha->format('Y-m-d'),
            'hora' => $cita->hora
        ]);

        return response()->json([
            'success' => true,
            'data' => $cita->load(['doctorRelation.especialidad', 'pacienteRelation.person']),
            'message' => 'Cita creada exitosamente'
        ], 201);
    }

    /**
     * Actualizar una cita
     */
    public function update(Request $request, $id)
    {
        $cita = Cita::find($id);
        
        if (!$cita) {
            return response()->json([
                'success' => false,
                'message' => 'Cita no encontrada'
            ], 404);
        }

        // VERIFICACIÓN DE PERMISOS
        $this->authorizeAppointmentAction($cita);
        
        // Si cambian de doctor, volver a verificar permisos sobre el NUEVO doctor
        if ($request->has('doctor_id') && $request->doctor_id != $cita->doctor_id) {
            $newDoctor = Doctor::find($request->doctor_id);
            $this->authorizeDoctorSelection($newDoctor);
        }

        $validator = Validator::make($request->all(), [
            'paciente_id' => 'nullable|exists:patients,id',
            'folio_expediente' => 'required|string|max:100',
            'paciente_nombre' => 'required|string|max:200',
            'fecha_nacimiento' => 'required|date|before_or_equal:today',
            'numero_cel' => 'required|string|max:20',
            'procedencia' => 'required|string|max:200',
            'tipo_cita' => 'required|in:Primera vez,Subsecuente',
            'turno' => 'required|in:Matutino,Vespertino',
            'paciente_telefono' => 'nullable|string|max:20',
            'paciente_email' => 'nullable|email|max:100',
            'doctor_id' => 'required|exists:doctors,id',
            'fecha' => 'required|date',
            'hora' => 'required|date_format:H:i',
            'motivo' => 'required|string',
            'observaciones' => 'nullable|string',
            'estado' => 'nullable|in:pendiente,confirmada,en_progreso,completada,cancelada,no_asistio',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar disponibilidad del doctor (excepto la cita actual)
        $conflicto = Cita::where('doctor_id', $request->doctor_id)
            ->where('fecha', $request->fecha)
            ->where('hora', $request->hora)
            ->where('id', '!=', $id)
            ->whereNotIn('estado', ['cancelada', 'no_asistio'])
            ->exists();

        if ($conflicto) {
            return response()->json([
                'success' => false,
                'message' => 'El doctor ya tiene una cita agendada en ese horario'
            ], 400);
        }

        // Guardar valores antiguos para el log
        $oldValues = [
            'doctor' => $cita->doctor['nombre'] ?? 'N/A',
            'fecha' => $cita->fecha->format('Y-m-d'),
            'estado' => $cita->estado
        ];

        $cita->update($request->all());

        // Registrar actividad de actualización
        $newValues = [
            'doctor' => $cita->doctor['nombre'] ?? 'N/A',
            'fecha' => $cita->fecha->format('Y-m-d'),
            'estado' => $cita->estado
        ];
        ActivityLoggerService::logUpdate('Cita', $cita->id, 'citas', $oldValues, $newValues);

        return response()->json([
            'success' => true,
            'data' => $cita->load(['doctorRelation.especialidad', 'pacienteRelation.person']),
            'message' => 'Cita actualizada exitosamente'
        ]);
    }

    /**
     * Cancelar una cita
     */
    public function cancel(Request $request, $id)
    {
        $cita = Cita::find($id);
        
        if (!$cita) {
            return response()->json([
                'success' => false,
                'message' => 'Cita no encontrada'
            ], 404);
        }

        // VERIFICACIÓN DE PERMISOS
        $this->authorizeAppointmentAction($cita);

        if (in_array($cita->estado, ['cancelada', 'completada'])) {
            return response()->json([
                'success' => false,
                'message' => 'La cita ya está cancelada o completada'
            ], 400);
        }

        $cita->update([
            'estado' => 'cancelada',
            'fecha_cancelacion' => now(),
            'motivo_cancelacion' => $request->get('motivo_cancelacion', 'Sin motivo especificado')
        ]);

        // Registrar actividad
        ActivityLoggerService::logUpdate('Cita', $cita->id, 'citas', 
            ['estado' => $cita->getOriginal('estado')],
            ['estado' => 'cancelada']
        );

        return response()->json([
            'success' => true,
            'data' => $cita,
            'message' => 'Cita cancelada exitosamente'
        ]);
    }

    /**
     * Eliminar una cita
     */
    public function destroy($id)
    {
        $cita = Cita::find($id);
        
        if (!$cita) {
            return response()->json([
                'success' => false,
                'message' => 'Cita no encontrada'
            ], 404);
        }

        // VERIFICACIÓN DE PERMISOS
        $this->authorizeAppointmentAction($cita);

        // Registrar actividad de eliminación
        ActivityLoggerService::logDelete('Cita', $cita->id, 'citas', [
            'doctor' => $cita->doctor['nombre'] ?? 'N/A',
            'fecha' => $cita->fecha->format('Y-m-d'),
            'paciente' => $cita->paciente['nombre'] ?? 'N/A'
        ]);

        $cita->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cita eliminada exitosamente'
        ]);
    }

    /**
     * Obtener citas del día
     */
    public function today()
    {
        $query = Cita::with(['doctorRelation.especialidad', 'pacienteRelation.person'])
            ->whereDate('fecha', today())
            ->orderBy('hora');

        $user = auth()->user();
        $canSpecialist = $user->can('schedule_specialist_appointment');
        $canGeneral = $user->can('schedule_general_appointment');

        if ($canSpecialist && !$canGeneral) {
            $query->whereHas('doctorRelation', function($q) {
                $q->whereNotNull('especialidad_id');
            });
        } elseif ($canGeneral && !$canSpecialist) {
            $query->whereHas('doctorRelation', function($q) {
                $q->whereNotNull('general_medical_id');
            });
        }

        $citas = $query->get();

        return response()->json([
            'success' => true,
            'data' => $citas,
            'total' => $citas->count()
        ]);
    }

    /**
     * Obtener horarios disponibles para un doctor en una fecha
     */
    public function getHorariosDisponibles(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'doctor_id' => 'required|exists:doctors,id',
                'fecha' => 'required|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $doctor = Doctor::with('especialidad')->findOrFail($request->doctor_id);
            
            // VERIFICAR PERMISO PARA VER HORARIOS DE ESTE DOCTOR
            $this->authorizeDoctorSelection($doctor);

            $fecha = $request->fecha;

            // Obtener citas ya agendadas para ese doctor en esa fecha (excluir canceladas y no asistidas)
            $citasAgendadas = Cita::where('doctor_id', $doctor->id)
                ->whereDate('fecha', $fecha)
                ->whereNotIn('estado', ['cancelada', 'no_asistio'])
                ->pluck('hora')
                ->map(function($hora) {
                    // Asegurar formato H:i
                    return substr($hora, 0, 5);
                })
                ->toArray();

            // Generar horarios disponibles según el turno del doctor
            $slots = $this->generarSlotsDeHorario($doctor, $citasAgendadas);

            ActivityLoggerService::logRead('Cita', null, 'horarios_disponibles', [
                'doctor_id' => $doctor->id,
                'fecha' => $fecha,
                'slots_disponibles' => count($slots)
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'fecha' => $fecha,
                    'doctor_id' => $doctor->id,
                    'turno' => $doctor->turno,
                    'slots' => $slots
                ],
                'message' => count($slots) > 0 ? 'Horarios encontrados' : 'No hay horarios configurados para este doctor'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en getHorariosDisponibles', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener horarios disponibles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: Autorizar selección de doctor basado en permisos
     */
    private function authorizeDoctorSelection($doctor)
    {
        $user = auth()->user();
        
        // Si tiene permiso de admin, pasa
        if ($user->can('admin_dashboard')) {
            return;
        }

        // Validar Especialista
        if ($doctor->especialidad_id) {
            // Si el usuario SOLO tiene permiso de general, y NO de especialista -> ERROR
            if ($user->can('schedule_general_appointment') && !$user->can('schedule_specialist_appointment')) {
                abort(403, 'No tiene permiso para gestionar citas con especialistas.');
            }
        }

        // Validar General
        if ($doctor->general_medical_id) {
            // Si el usuario SOLO tiene permiso de especialista, y NO de general -> ERROR
            if ($user->can('schedule_specialist_appointment') && !$user->can('schedule_general_appointment')) {
                abort(403, 'No tiene permiso para gestionar citas con médicos generales.');
            }
        }
    }

    /**
     * Helper: Autorizar acción sobre una cita existente
     */
    private function authorizeAppointmentAction($cita)
    {
        if ($cita->doctorRelation) {
            $this->authorizeDoctorSelection($cita->doctorRelation);
        } else {
            // Fallback si no hay relación cargada, cargarla
            $doctor = Doctor::find($cita->doctor_id);
            if ($doctor) {
                $this->authorizeDoctorSelection($doctor);
            }
        }
    }

    /**
     * Generar slots de horarios de 1 hora
     */
    private function generarSlotsDeHorario($doctor, $citasAgendadas)
    {
        $duracion = 20; // minutos
        $turno = $doctor->turno;

        // Normalizar turno
        $turnoLower = strtolower($turno);

        // Horarios según el turno
        if ($turnoLower === 'matutino') {
            $inicio = $doctor->hora_inicio_matutino ?: '08:00';
            $fin = $doctor->hora_fin_matutino ?: '14:00';
            return $this->generarSlotsPorRango($inicio, $fin, $duracion, $citasAgendadas);
        } 
        
        if ($turnoLower === 'vespertino') {
            $inicio = $doctor->hora_inicio_vespertino ?: '14:00';
            $fin = $doctor->hora_fin_vespertino ?: '20:00';
            return $this->generarSlotsPorRango($inicio, $fin, $duracion, $citasAgendadas);
        }
        
        // Turno mixto - generar para ambos turnos
        $slotsMatutino = $this->generarSlotsPorRango(
            $doctor->hora_inicio_matutino ?: '08:00',
            $doctor->hora_fin_matutino ?: '14:00',
            $duracion,
            $citasAgendadas
        );
        
        $slotsVespertino = $this->generarSlotsPorRango(
            $doctor->hora_inicio_vespertino ?: '14:00',
            $doctor->hora_fin_vespertino ?: '20:00',
            $duracion,
            $citasAgendadas
        );
        
        return array_merge($slotsMatutino, $slotsVespertino);
    }

    /**
     * Generar slots por rango de horas
     */
    private function generarSlotsPorRango($inicio, $fin, $duracion, $citasAgendadas)
    {
        $slots = [];
        
        if (!$inicio || !$fin) {
            return $slots;
        }

        try {
            // Parse flexible - acepta tanto HH:MM como HH:MM:SS
            $horaActual = Carbon::parse($inicio);
            $horaFin = Carbon::parse($fin);

            while ($horaActual->lt($horaFin)) {
                $horaString = $horaActual->format('H:i');
                $slots[] = [
                    'hora' => $horaString,
                    'disponible' => !in_array($horaString, $citasAgendadas)
                ];
                $horaActual->addMinutes($duracion);
            }
        } catch (\Exception $e) {
            \Log::error('Error generando slots', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'inicio' => $inicio,
                'inicio_type' => gettype($inicio),
                'fin' => $fin,
                'fin_type' => gettype($fin)
            ]);
        }

        return $slots;
    }

    /**
     * Obtener estadísticas de citas
     */
    public function stats()
    {
        $total = Cita::count();
        $pendientes = Cita::where('estado', 'pendiente')->count();
        $confirmadas = Cita::where('estado', 'confirmada')->count();
        $completadas = Cita::where('estado', 'completada')->count();
        $canceladas = Cita::where('estado', 'cancelada')->count();
        $hoy = Cita::whereDate('fecha', today())->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'pendientes' => $pendientes,
                'confirmadas' => $confirmadas,
                'completadas' => $completadas,
                'canceladas' => $canceladas,
                'hoy' => $hoy
            ]
        ]);
    }
}
