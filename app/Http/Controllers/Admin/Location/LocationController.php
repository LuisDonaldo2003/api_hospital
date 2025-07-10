<?php

namespace App\Http\Controllers\Admin\Location;

use App\Models\Location;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LocationController extends Controller
{
    public function byMunicipality(Request $request)
    {
        $request->validate(['municipality_id' => 'required|integer|exists:municipalities,id']);

        return response()->json(
            Location::where('municipality_id', $request->municipality_id)
                ->select('id', 'name', 'municipality_id')
                ->orderBy('name')
                ->get()
        );
    }
}
