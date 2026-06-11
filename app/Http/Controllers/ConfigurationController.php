<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConfigurationController extends Controller
{
    public function index()
    {
        $configs = Configuration::pluck('value', 'key')->toArray();

        return Inertia::render('Configuracion/Index', [
            'configs' => $configs
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array'
        ]);

        foreach ($validated['settings'] as $key => $value) {
            Configuration::set($key, $value);
        }

        return redirect()->back()->with('success', 'Configuración guardada exitosamente.');
    }
}
