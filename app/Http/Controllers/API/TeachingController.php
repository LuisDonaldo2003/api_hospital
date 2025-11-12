<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Teaching;
use App\Http\Requests\StoreTeachingRequest;
use App\Http\Requests\UpdateTeachingRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeachingController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $query = Teaching::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('nombre_evento', 'like', "%{$search}%")
                  ->orWhere('profesion', 'like', "%{$search}%");
            });
        }

        if ($especialidad = $request->get('especialidad')) {
            $query->where('area', $especialidad);
        }

        if ($modalidad = $request->get('modalidad_id')) {
            $query->where('modalidad_id', $modalidad);
        }

        if ($participacion = $request->get('participacion_id')) {
            $query->where('participacion_id', $participacion);
        }

        if ($fechaInicio = $request->get('fecha_inicio')) {
            $query->whereDate('fecha', '>=', $fechaInicio);
        }

        if ($fechaFin = $request->get('fecha_fin')) {
            $query->whereDate('fecha', '<=', $fechaFin);
        }

        if ($nombre_evento = $request->get('nombre_evento')) {
            $query->where('nombre_evento', 'like', "%{$nombre_evento}%");
        }

        $p = $query->orderBy('fecha', 'desc')->paginate($perPage);

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
        $teaching = Teaching::find($id);
        if (!$teaching) {
            return response()->json(['success' => false, 'message' => 'No encontrado'], 404);
        }
        return response()->json(['success' => true, 'data' => $teaching]);
    }

    public function store(StoreTeachingRequest $request)
    {
        $data = $request->validated();
        $teaching = Teaching::create($data);
        return response()->json(['success' => true, 'data' => $teaching]);
    }

    public function update(UpdateTeachingRequest $request, $id)
    {
        $teaching = Teaching::find($id);
        if (!$teaching) {
            return response()->json(['success' => false, 'message' => 'No encontrado'], 404);
        }
        $teaching->update($request->validated());
        return response()->json(['success' => true, 'data' => $teaching]);
    }

    public function destroy($id)
    {
        $teaching = Teaching::find($id);
        if (!$teaching) {
            return response()->json(['success' => false, 'message' => 'No encontrado'], 404);
        }
        $teaching->delete();
        return response()->json(['success' => true, 'message' => 'Eliminado correctamente']);
    }

    public function stats()
    {
        $total = Teaching::count();
        $porModalidad = Teaching::select('modalidad_id', \DB::raw('count(*) as total'))
            ->groupBy('modalidad_id')->get()->pluck('total', 'modalidad_id');
        $porParticipacion = Teaching::select('participacion_id', \DB::raw('count(*) as total'))
            ->groupBy('participacion_id')->get()->pluck('total', 'participacion_id');

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'por_modalidad' => $porModalidad,
                'por_participacion' => $porParticipacion,
                'total_horas' => 0,
                'evaluaciones_pendientes' => \App\Models\Evaluacion::where('estado', 'PENDIENTE')->count(),
            ]
        ]);
    }

    public function export(Request $request)
    {
        $query = Teaching::query();

        // Apply simple filters (same as index)
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('nombre_evento', 'like', "%{$search}%");
            });
        }

        $records = $query->orderBy('fecha', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="teachings_export.csv"',
        ];

        $columns = [
            'id','correo','ei','ef','profesion','nombre','area','adscripcion','nombre_evento','tema','fecha','horas','foja','modalidad_id','participacion_id'
        ];

        $callback = function () use ($records, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($records as $r) {
                $row = [];
                foreach ($columns as $c) {
                    $row[] = $r->{$c} ?? '';
                }
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['success' => false, 'message' => 'No se recibió archivo'], 400);
        }

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = null;
        $imported = 0;
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if (!$header) {
                $header = $row;
                continue;
            }
            $data = array_combine($header, $row);
            // Map only known fields
            $teachingData = array_intersect_key($data, array_flip(["correo","ei","ef","profesion","nombre","area","adscripcion","nombre_evento","tema","fecha","horas","foja","modalidad_id","participacion_id"]));
            // Convert fecha if exists
            if (!empty($teachingData['fecha'])) {
                $teachingData['fecha'] = date('Y-m-d', strtotime($teachingData['fecha']));
            }
            Teaching::updateOrCreate(
                ['nombre' => $teachingData['nombre'], 'nombre_evento' => $teachingData['nombre_evento'], 'fecha' => $teachingData['fecha'] ?? null],
                $teachingData
            );
            $imported++;
        }
        fclose($handle);

        return response()->json(['success' => true, 'message' => "Importadas: {$imported}"]);
    }

    public function getModalidades()
    {
        $modalidades = \DB::table('modalidades')->where('activo', true)->get();
        return response()->json(['success' => true, 'data' => $modalidades]);
    }

    public function getParticipaciones()
    {
        $participaciones = \DB::table('participaciones')->where('activo', true)->get();
        return response()->json(['success' => true, 'data' => $participaciones]);
    }

    public function getProfesiones()
    {
        // Obtener profesiones únicas de los registros existentes
        $profesiones = Teaching::select('profesion')
            ->whereNotNull('profesion')
            ->where('profesion', '!=', '')
            ->distinct()
            ->orderBy('profesion')
            ->pluck('profesion');
        
        // Agregar profesiones comunes si no existen
        $profesionesComunes = ['DR.', 'DRA.', 'MIP.', 'EPSS.', 'MDO.', 'LE.', 'L.E.', 'E.L.E', 'ELE.', 'TR.', 'EEI.'];
        $todasProfesiones = $profesiones->merge($profesionesComunes)->unique()->values();
        
        return response()->json(['success' => true, 'data' => $todasProfesiones]);
    }

    public function getAreas()
    {
        // Obtener áreas únicas de los registros existentes
        $areas = Teaching::select('area')
            ->whereNotNull('area')
            ->where('area', '!=', '')
            ->distinct()
            ->orderBy('area')
            ->pluck('area');
        
        // Agregar áreas comunes si no existen
        $areasComunes = ['MEDICINA', 'ENFERMERIA', 'MEDICO INTERNO DE PREGRADO', 'ENFERMERO PASANTE DE SERVICIO SOCIAL', 'ADMINISTRATIVA'];
        $todasAreas = $areas->merge($areasComunes)->unique()->values();
        
        return response()->json(['success' => true, 'data' => $todasAreas]);
    }
}
