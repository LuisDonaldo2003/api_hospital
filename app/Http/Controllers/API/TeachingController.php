<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TeachingAssistant;
use App\Models\TeachingEvent;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\ActivityLoggerService;

class TeachingController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $query = TeachingAssistant::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('profesion', 'like', "%{$search}%")
                  ->orWhereHas('events', function($q2) use ($search) {
                      $q2->where('nombre_evento', 'like', "%{$search}%");
                  });
            });
        }

        if ($especialidad = $request->get('especialidad')) {
            $query->where('profesion', $especialidad);
        }

        if ($area = $request->get('area')) {
            $query->where('area', $area);
        }

        // Filters that apply to Events
        if ($modalidad = $request->get('modalidad_id')) {
            $query->whereHas('events', function($q) use ($modalidad) {
                $q->where('modalidad_id', $modalidad);
            });
        }

        if ($participacion = $request->get('participacion_id')) {
            $query->whereHas('events', function($q) use ($participacion) {
                $q->where('participacion_id', $participacion);
            });
        }

        if ($fechaInicio = $request->get('fecha_inicio')) {
            $query->whereHas('events', function($q) use ($fechaInicio) {
                $q->whereDate('fecha', '>=', $fechaInicio);
            });
        }

        if ($fechaFin = $request->get('fecha_fin')) {
            $query->whereHas('events', function($q) use ($fechaFin) {
                $q->whereDate('fecha', '<=', $fechaFin);
            });
        }

        if ($nombre_evento = $request->get('nombre_evento')) {
            $query->whereHas('events', function($q) use ($nombre_evento) {
                $q->where('nombre_evento', 'like', "%{$nombre_evento}%");
            });
        }

        $sortDirection = $request->get('sort_direction', 'desc');
        $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc']) ? $sortDirection : 'desc';

        // Sort by name or ID
        $p = $query->orderBy('id', $sortDirection)->withCount('events')->with('events')->paginate($perPage);

        // Registrar actividad de lectura
        ActivityLoggerService::logRead('Assistant', null, 'teaching_assistants', [
            'total_records' => $p->total()
        ]);

        return response()->json([
            'success' => true,
            'data' => $p->items(),
            'total' => $p->total(),
            'per_page' => $p->perPage(),
            'current_page' => $p->currentPage(),
            'last_page' => $p->lastPage(),
            'from' => $p->firstItem(),
            'to' => $p->lastItem(),
        ]);
    }

    public function show($id)
    {
        $assistant = TeachingAssistant::with('events')->find($id);
        if (!$assistant) {
            return response()->json(['success' => false, 'message' => 'No encontrado'], 404);
        }

        // Registrar actividad de lectura
        ActivityLoggerService::logRead('Assistant', $assistant->id, 'teaching_assistants', [
            'nombre' => $assistant->nombre
        ]);

        return response()->json(['success' => true, 'data' => $assistant]);
    }

    public function store(Request $request)
    {
        // This endpoint might now be used to create an assistant OR add an event?
        // Let's assume it creates a new Assistant with an initial Event, or just an Assistant.
        // Or if we want to add an event to an existing assistant, we might check if assistant exists.
        
        // Validation should be updated in a FormRequest, but for now doing inline or reusing existing validation if compatible.
        // Existing StoreTeachingRequest is likely incompatible/needs update.
        
        $data = $request->validate([
            'nombre' => 'required|string',
            'profesion' => 'nullable|string',
            'area' => 'nullable|string', 
            'adscripcion' => 'nullable|string',
            'nombre_evento' => 'nullable|string', // Event details
            'tema' => 'nullable|string',
            'fecha' => 'nullable|date',
            'horas' => 'nullable|string',
            'modalidad_id' => 'nullable|integer',
            'participacion_id' => 'nullable|integer',
        ]);

        DB::beginTransaction();
        try {
            $assistant = TeachingAssistant::firstOrCreate(
                ['nombre' => $data['nombre']],
                [
                    'profesion' => $data['profesion'] ?? null,
                    'area' => $data['area'] ?? null,
                    'adscripcion' => $data['adscripcion'] ?? null,
                ]
            );

            // Create event if event data is present
            if (!empty($data['nombre_evento'])) {
                $assistant->events()->create([
                    'nombre_evento' => $data['nombre_evento'],
                    'tema' => $data['tema'] ?? null,
                    'fecha' => $data['fecha'] ?? null,
                    'horas' => $data['horas'] ?? null,
                    'modalidad_id' => $data['modalidad_id'] ?? null,
                    'participacion_id' => $data['participacion_id'] ?? null,
                ]);
            }
            
            DB::commit();

            ActivityLoggerService::logCreate('Assistant', $assistant->id, 'teaching_assistants', [
                'nombre' => $assistant->nombre
            ]);

            return response()->json(['success' => true, 'data' => $assistant->load('events')]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        // Update Assistant Info
        $assistant = TeachingAssistant::find($id);
        if (!$assistant) {
            return response()->json(['success' => false, 'message' => 'No encontrado'], 404);
        }

        $assistant->update($request->only(['nombre', 'profesion', 'area', 'adscripcion', 'correo']));

        ActivityLoggerService::logUpdate('Assistant', $assistant->id, 'teaching_assistants', [], []);

        return response()->json(['success' => true, 'data' => $assistant]);
    }

    public function destroy($id)
    {
        $assistant = TeachingAssistant::find($id);
        if (!$assistant) {
            return response()->json(['success' => false, 'message' => 'No encontrado'], 404);
        }

        ActivityLoggerService::logDelete('Assistant', $assistant->id, 'teaching_assistants', [
            'nombre' => $assistant->nombre
        ]);

        $assistant->delete(); // Cascades events because of migration foreign key? Migration said onDelete('cascade')
        
        return response()->json(['success' => true, 'message' => 'Eliminado correctamente']);
    }
    

    
    public function storeEvent(Request $request, $assistantId) {
        $assistant = TeachingAssistant::find($assistantId);
        if (!$assistant) return response()->json(['success' => false, 'message' => 'Asistente no encontrado'], 404);
        
        $event = $assistant->events()->create($request->all());
        return response()->json(['success' => true, 'data' => $event]);
    }
    
    public function updateEvent(Request $request, $eventId) {
        $event = TeachingEvent::find($eventId);
        if (!$event) return response()->json(['success' => false, 'message' => 'Evento no encontrado'], 404);
        
        $event->update($request->all());
        return response()->json(['success' => true, 'data' => $event]);
    }
    
    public function destroyEvent($eventId) {
        $event = TeachingEvent::find($eventId);
        if (!$event) return response()->json(['success' => false, 'message' => 'Evento no encontrado'], 404);
        
        $event->delete();
        return response()->json(['success' => true, 'message' => 'Evento eliminado']);
    }

    public function stats()
    {
        $total = TeachingAssistant::count();
        // Stats calculations might need to be adjusted to count EVENTS now?
        // "porModalidad" implies counting events.
        
        $porModalidad = TeachingEvent::select('modalidad_id', DB::raw('count(*) as total'))
            ->groupBy('modalidad_id')->get()->pluck('total', 'modalidad_id');
            
        $porParticipacion = TeachingEvent::select('participacion_id', DB::raw('count(*) as total'))
            ->groupBy('participacion_id')->get()->pluck('total', 'participacion_id');

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'por_modalidad' => $porModalidad,
                'por_participacion' => $porParticipacion,
                'total_horas' => 0, // calc if needed
                'evaluaciones_pendientes' => \App\Models\Evaluacion::where('estado', 'PENDIENTE')->count(),
            ]
        ]);
    }

    public function export(Request $request)
    {
        // Export flattened data for compatibility?
        $query = TeachingEvent::with('assistant');

        // Apply filters... (similar to index but on Event directly)
        
        $records = $query->orderBy('fecha', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="teachings_export.csv"',
        ];

        $columns = [
            'nombre', 'profesion', 'area', 'nombre_evento', 'tema', 'fecha', 'horas'
        ];

        $callback = function () use ($records, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($records as $r) {
                $row = [
                    $r->assistant->nombre ?? '',
                    $r->assistant->profesion ?? '',
                    $r->assistant->area ?? '',
                    $r->nombre_evento,
                    $r->tema,
                    $r->fecha,
                    $r->horas
                ];
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    public function getModalidades()
    {
        $modalidades = DB::table('modalidades')->where('activo', true)->get();
        return response()->json(['success' => true, 'data' => $modalidades]);
    }

    public function getParticipaciones()
    {
        $participaciones = DB::table('participaciones')->where('activo', true)->get();
        return response()->json(['success' => true, 'data' => $participaciones]);
    }

    public function getProfesiones()
    {
        $profesiones = DB::table('profesiones')->where('activo', true)->orderBy('nombre')->pluck('nombre');
        return response()->json(['success' => true, 'data' => $profesiones]);
    }

    public function getAreas()
    {
        $areas = DB::table('areas')->where('activo', true)->orderBy('nombre')->pluck('nombre');
        return response()->json(['success' => true, 'data' => $areas]);
    }
}
