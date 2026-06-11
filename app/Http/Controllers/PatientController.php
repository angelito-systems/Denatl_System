<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use Inertia\Inertia;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Patient::query();
        if ($request->has('search') && $request->input('search') !== '') {
            $search = $request->input('search');
            $query->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('dni', 'like', "%{$search}%");
        }

        $patients = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('Patients/Index', [
            'patients' => $patients,
            'filters' => $request->only(['search'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Patients/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePatientRequest $request)
    {
        $patient = Patient::create($request->validated());
        return redirect()->route('patients.show', $patient->id)->with('success', 'Patient created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Patient $patient)
    {
        $patient->load(['documents.media']);
        
        return Inertia::render('Patients/Show', [
            'patient' => $patient
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Patient $patient)
    {
        return Inertia::render('Patients/Edit', [
            'patient' => $patient
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePatientRequest $request, Patient $patient)
    {
        $patient->update($request->validated());
        return redirect()->route('patients.show', $patient->id)->with('success', 'Patient updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Patient $patient)
    {
        $patient->delete();
        return redirect()->back()->with('success', 'Paciente eliminado exitosamente.');
    }

    public function downloadHistoria(Patient $patient, \App\Services\PdfGeneratorService $pdfService)
    {
        return $pdfService->generarHistoriaClinica($patient)->download();
    }

    public function downloadContrato(Request $request, Patient $patient, \App\Services\PdfGeneratorService $pdfService)
    {
        $plantilla = $request->input('plantilla', 'contrato');
        return $pdfService->generarPlantilla($patient, $plantilla)->inline("{$plantilla}_{$patient->id}.pdf");
    }
}
