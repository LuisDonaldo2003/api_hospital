<?php

namespace App\Http\Controllers\Admin\Departament;

use App\Http\Controllers\Controller;
use App\Models\Departaments;
use Illuminate\Http\Request;

class DepartamentController extends Controller
{
    public function index(Request $request)
    {
        // QUE EL FILTRO POR NOMBRE DE ROL
        $name = $request->search;

        $departaments = Departaments::where("name","like","%".$name."%")->orderBy("id","desc")->get();

        return response()->json([
            "departaments" => $departaments->map(function($rol) {
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
        $is_departaments = Departaments::where("name",$request->name)->first();

        if($is_departaments){
            return response()->json([
                "message" => 403,
                "message_text" => "EL NOMBRE DEL DEPARTAMENTO YA EXISTE"
            ]);
        }

        $specialitie = Departaments::create($request->all());

        return response()->json([
            "message" => 200,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $departaments = Departaments::findOrFail($id);
        return response()->json([
            "id" => $departaments->id,
            "name" => $departaments->name,
            "state" => $departaments->state
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $is_departaments = Departaments::where("id","<>",$id)->where("name",$request->name)->first();

        if($is_departaments){
            return response()->json([
                "message" => 403,
                "message_text" => "EL NOMBRE DEL DEPARTAMENTO YA EXISTE"
            ]);
        }

        $departaments = Departaments::findOrFail($id);
        $departaments->update($request->all());
        return response()->json([
            "message" => 200,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $departaments = Departaments::findOrFail($id);
        $departaments->delete();
        return response()->json([
            "message" => 200,
        ]);
    }
}
