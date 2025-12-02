<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Evaluacion;
use App\Http\Requests\StoreEvaluacionRequest;
use App\Services\ActivityLoggerService;

class EvaluacionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $q = Evaluacion::query();
        $p = $q->orderBy('fecha_inicio')->paginate($perPage);

        ActivityLoggerService::logRead('Evaluation', null, 'evaluaciones', ['total_records' => $p->total()]);

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

    public function pendientes()
    {
        $p = Evaluacion::where('estado', 'PENDIENTE')->orderBy('fecha_inicio')->paginate(15);

        ActivityLoggerService::logRead('Evaluation', null, 'evaluaciones', [
            'filter' => 'pendientes',
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
        $evaluacion = Evaluacion::find($id);
        if (!$evaluacion) {
            return response()->json([
                'success' => false, 
                'message' => 'Evaluación no encontrada'
            ], 404);
        }

        ActivityLoggerService::logRead('Evaluation', $evaluacion->id, 'evaluaciones', ['teaching_id' => $evaluacion->teaching_id]);

        return response()->json([
            'success' => true, 
            'data' => $evaluacion
        ]);
    }

    public function stats()
    {
        $total = Evaluacion::count();
        $pendientes = Evaluacion::where('estado', 'PENDIENTE')->count();
        $aprobadas = Evaluacion::where('estado', 'APROBADO')->count();
        $reprobadas = Evaluacion::where('estado', 'REPROBADO')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'pendientes' => $pendientes,
                'aprobadas' => $aprobadas,
                'reprobadas' => $reprobadas
            ]
        ]);
    }

    public function store(StoreEvaluacionRequest $request)
    {
        $data = $request->validated();
        $evaluacion = Evaluacion::create($data);

        ActivityLoggerService::logCreate('Evaluation', $evaluacion->id, 'evaluaciones', ['teaching_id' => $evaluacion->teaching_id]);

        return response()->json(['success' => true, 'data' => $evaluacion]);
    }

    public function update(Request $request, $id)
    {
        $evaluacion = Evaluacion::find($id);
        if (!$evaluacion) return response()->json(['success' => false, 'message' => 'No encontrado'], 404);

        $oldValues = ['estado' => $evaluacion->estado];
        $evaluacion->update($request->all());
        $newValues = ['estado' => $evaluacion->estado];

        ActivityLoggerService::logUpdate('Evaluation', $evaluacion->id, 'evaluaciones', $oldValues, $newValues);

        return response()->json(['success' => true, 'data' => $evaluacion]);
    }

    public function destroy($id)
    {
        $evaluacion = Evaluacion::find($id);
        if (!$evaluacion) return response()->json(['success' => false, 'message' => 'No encontrado'], 404);

        ActivityLoggerService::logDelete('Evaluation', $evaluacion->id, 'evaluaciones', ['teaching_id' => $evaluacion->teaching_id]);

        $evaluacion->delete();
        return response()->json(['success' => true, 'message' => 'Eliminada']);
    }
}
