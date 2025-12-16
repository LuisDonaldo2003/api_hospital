<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Configuration;
// Request already imported above

class ConfigurationController extends Controller
{
    public function getArchiveConfig()
    {
        $config = Configuration::where('key', 'archive_start_number')->first();
        return response()->json([
            'archive_start_number' => $config ? (int)$config->value : 0
        ]);
    }

    public function updateArchiveConfig(Request $request)
    {
        $request->validate([
            'archive_start_number' => 'required|integer|min:0'
        ]);

        $config = Configuration::updateOrCreate(
            ['key' => 'archive_start_number'],
            ['value' => $request->archive_start_number]
        );

        return response()->json([
            'message' => 'Configuración actualizada correctamente',
            'config' => $config
        ]);
    }
}
