<?php

namespace App\Http\Controllers\Admin\ContractTypes;

use App\Models\ContractType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        // QUE EL FILTRO POR NOMBRE DE ROL
        $name = $request->search;

        $contracts = ContractType::where("name","like","%".$name."%")->orderBy("id","desc")->get();

        return response()->json([
            "contracts" => $contracts->map(function($rol) {
                return [
                    "id" => $rol->id,
                    "name" => $rol->name,
                    "state" => $rol->state,
                    "created_at" => $rol->created_at->format("Y-m-d h:i:s")
                ];
            }),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $is_contracts = ContractType::where("name",$request->name)->first();

        if($is_contracts){
            return response()->json([
                "message" => 403,
                "message_text" => "EL NOMBRE DEL CONTRATO YA EXISTE"
            ]);
        }

        $contracts = ContractType::create($request->all());

        return response()->json([
            "message" => 200,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $contracts = ContractType::findOrFail($id);
        return response()->json([
            "id" => $contracts->id,
            "name" => $contracts->name,
            "state" => $contracts->state
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $is_contracts = ContractType::where("id","<>",$id)->where("name",$request->name)->first();

        if($is_contracts){
            return response()->json([
                "message" => 403,
                "message_text" => "EL NOMBRE DEL CONTRATO YA EXISTE"
            ]);
        }

        $contracts = ContractType::findOrFail($id);
        $contracts->update($request->all());
        return response()->json([
            "message" => 200,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $contracts = ContractType::findOrFail($id);
        $contracts->delete();
        return response()->json([
            "message" => 200,
        ]);
    }
}
