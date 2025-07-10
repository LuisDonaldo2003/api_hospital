<?php

namespace App\Http\Controllers\Admin\Municipality;

use App\Models\Municipality;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MunicipalityController extends Controller
{
    public function byState(Request $request)
    {
        $request->validate(['state_id' => 'required|integer|exists:states,id']);

        return response()->json(
            Municipality::where('state_id', $request->state_id)
                ->select('id', 'name', 'state_id')
                ->orderBy('name')
                ->get()
        );
    }
}
