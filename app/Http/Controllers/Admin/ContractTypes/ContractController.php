<?php

namespace App\Http\Controllers\Admin\ContractTypes;

use App\Models\ContractType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\ActivityLoggerService;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $query = ContractType::query();

        if ($request->filled('search')) {
            $query->where("name", "like", "%" . $request->search . "%");
        }

        $contracts = $query->orderBy("id", "desc")->get();

        // Log the list action
        ActivityLoggerService::logRead('ContractType', null, 'contract-types', [
            'search_term' => $request->search,
            'total_results' => $contracts->count()
        ]);

        return response()->json([
            "contracts" => $contracts->map(function ($contract) {
                return [
                    "id" => $contract->id,
                    "name" => $contract->name,
                    "state" => $contract->state,
                    "created_at" => $contract->created_at->format("Y-m-d h:i:s")
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'state' => 'required|boolean',
        ]);

        if (ContractType::where("name", $request->name)->exists()) {
            return response()->json([
                "message" => 403,
                "message_text" => "EL NOMBRE DEL CONTRATO YA EXISTE"
            ]);
        }

        $contracts = ContractType::create($request->all());

        // Log the creation activity
        ActivityLoggerService::logCreate('ContractType', $contracts->id, 'contract-types', [
            'name' => $contracts->name,
            'state' => $contracts->state
        ]);

        return response()->json([
            "message" => 200,
            "message_text" => "Contrato creado correctamente",
            "contract" => [
                "id" => $contracts->id,
                "name" => $contracts->name,
                "state" => $contracts->state,
            ]
        ]);
    }

    public function show(string $id)
    {
        $contract = ContractType::findOrFail($id);

        // Log the read activity
        ActivityLoggerService::logRead('ContractType', $contract->id, 'contract-types', [
            'name' => $contract->name,
            'state' => $contract->state
        ]);

        return response()->json([
            "id" => $contract->id,
            "name" => $contract->name,
            "state" => $contract->state
        ]);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'state' => 'required|boolean',
        ]);

        if (ContractType::where("id", "<>", $id)->where("name", $request->name)->exists()) {
            return response()->json([
                "message" => 403,
                "message_text" => "EL NOMBRE DEL CONTRATO YA EXISTE"
            ]);
        }

        $contract = ContractType::findOrFail($id);
        
        // Store old values for logging
        $oldValues = [
            'name' => $contract->name,
            'state' => $contract->state
        ];
        
        $contract->update($request->all());

        // Log the update activity
        ActivityLoggerService::logUpdate('ContractType', $contract->id, 'contract-types', $oldValues, [
            'name' => $contract->name,
            'state' => $contract->state
        ]);

        return response()->json([
            "message" => 200,
            "message_text" => "Contrato actualizado correctamente",
            "contract" => [
                "id" => $contract->id,
                "name" => $contract->name,
                "state" => $contract->state,
            ]
        ]);
    }

    public function destroy(string $id)
    {
        $contract = ContractType::findOrFail($id);
        
        // Log the deletion activity
        ActivityLoggerService::logDelete('ContractType', $contract->id, 'contract-types', [
            'name' => $contract->name,
            'state' => $contract->state
        ]);
        
        $contract->delete();

        return response()->json([
            "message" => 200,
            "message_text" => "Contrato eliminado correctamente"
        ]);
    }
}
