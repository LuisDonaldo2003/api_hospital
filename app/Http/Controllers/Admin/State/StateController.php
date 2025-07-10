<?php

namespace App\Http\Controllers\Admin\State;

use App\Models\State;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StateController extends Controller
{
    public function index()
    {
        return response()->json(
            State::select('id', 'name')
                ->orderBy('name')
                ->get()
        );
    }
}
