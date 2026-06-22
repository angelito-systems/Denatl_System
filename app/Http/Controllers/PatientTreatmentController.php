<?php

namespace App\Http\Controllers;

use App\Models\PatientTreatment;
use Illuminate\Http\Request;

class PatientTreatmentController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'name' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'estimated_end_date' => 'nullable|date',
            'status' => 'required|in:Activo,Pausado,Finalizado,Cancelado',
            'objectives' => 'nullable|string',
            'description' => 'nullable|string',
            'dentist_id' => 'nullable|exists:users,id',
        ]);

        PatientTreatment::create($validated);

        return back()->with('success', 'Tratamiento creado exitosamente');
    }

    public function update(Request $request, PatientTreatment $patientTreatment)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'estimated_end_date' => 'nullable|date',
            'status' => 'required|in:Activo,Pausado,Finalizado,Cancelado',
            'objectives' => 'nullable|string',
            'description' => 'nullable|string',
            'dentist_id' => 'nullable|exists:users,id',
        ]);

        $patientTreatment->update($validated);

        return back()->with('success', 'Tratamiento actualizado exitosamente');
    }

    public function destroy(PatientTreatment $patientTreatment)
    {
        $patientTreatment->delete();

        return back()->with('success', 'Tratamiento eliminado exitosamente');
    }
}
