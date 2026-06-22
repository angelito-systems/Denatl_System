<?php

namespace App\Http\Controllers;

use App\Models\Observation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ObservationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'content' => 'required|string',
        ]);

        $validated['user_id'] = Auth::id();

        Observation::create($validated);

        return back()->with('success', 'Observación guardada exitosamente');
    }

    public function update(Request $request, Observation $observation)
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $observation->update($validated);

        return back()->with('success', 'Observación actualizada exitosamente');
    }

    public function destroy(Observation $observation)
    {
        $observation->delete();

        return back()->with('success', 'Observación eliminada exitosamente');
    }
}
