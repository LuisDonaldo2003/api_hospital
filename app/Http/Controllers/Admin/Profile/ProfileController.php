<?php

namespace App\Http\Controllers\Admin\Profile;

use App\Models\Profile;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        // QUE EL FILTRO POR NOMBRE DE ROL
        $name = $request->search;

        $profile = Profile::where("name","like","%".$name."%")->orderBy("id","desc")->get();

        return response()->json([
            "profile" => $profile->map(function($rol) {
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
        $is_profile = Profile::where("name",$request->name)->first();

        if($is_profile){
            return response()->json([
                "message" => 403,
                "message_text" => "EL PERFIL YA EXISTE"
            ]);
        }

        $profile = Profile::create($request->all());

        return response()->json([
            "message" => 200,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $profile = Profile::findOrFail($id);
        return response()->json([
            "id" => $profile->id,
            "name" => $profile->name,
            "state" => $profile->state
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $is_profile = Profile::where("id","<>",$id)->where("name",$request->name)->first();

        if($is_profile){
            return response()->json([
                "message" => 403,
                "message_text" => "EL PERFIL YA EXISTE"
            ]);
        }

        $profile = Profile::findOrFail($id);
        $profile->update($request->all());
        return response()->json([
            "message" => 200,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $profile = Profile::findOrFail($id);
        $profile->delete();
        return response()->json([
            "message" => 200,
        ]);
    }
}
