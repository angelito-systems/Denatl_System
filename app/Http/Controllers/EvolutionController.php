<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;

class EvolutionController extends Controller
{
    public function store(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'description' => 'required|string',
            'odontogram_data' => 'nullable|array',
            'appointment_id' => 'nullable|exists:appointments,id',
        ]);

        $evolution = $patient->evolutions()->create([
            'dentist_id' => auth()->id() ?? User::first()->id ?? 1,
            'appointment_id' => $validated['appointment_id'],
            'description' => $validated['description'],
            'odontogram_data' => $validated['odontogram_data'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Evolución clínica guardada exitosamente.');
    }
}
