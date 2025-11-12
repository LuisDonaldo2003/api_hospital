<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Evaluacion;
use App\Http\Requests\StoreEvaluacionRequest;

class EvaluacionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $q = Evaluacion::query();
        $p = $q->orderBy('fecha_inicio')->paginate($perPage);

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

    public function store(StoreEvaluacionRequest $request)
    {
        $data = $request->validated();
        $evaluacion = Evaluacion::create($data);
        return response()->json(['success' => true, 'data' => $evaluacion]);
    }

    public function update(Request $request, $id)
    {
        $evaluacion = Evaluacion::find($id);
        if (!$evaluacion) return response()->json(['success' => false, 'message' => 'No encontrado'], 404);
        $evaluacion->update($request->all());
        return response()->json(['success' => true, 'data' => $evaluacion]);
    }

    public function destroy($id)
    {
        $evaluacion = Evaluacion::find($id);
        if (!$evaluacion) return response()->json(['success' => false, 'message' => 'No encontrado'], 404);
        $evaluacion->delete();
        return response()->json(['success' => true, 'message' => 'Eliminada']);
    }
}
