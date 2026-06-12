<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Patient;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePatientRequest $request)
    {
        $patient = Patient::create($request->validated());

        return redirect()->back()->with('success', 'Patient created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Patient $patient)
    {
        $patient->load(['documents.media', 'evolutions.dentist', 'evolutions.appointment', 'appointments', 'treatmentContracts.document', 'treatmentContracts.payments']);

        $latestOdontogram = $patient->evolutions()
            ->whereNotNull('odontogram_data')
            ->latest()
            ->first();

        $treatments = \App\Models\Treatment::orderBy('name')->get();

        return Inertia::render('Patients/Show', [
            'patient' => $patient,
            'treatments' => $treatments,
            'latestOdontogram' => $latestOdontogram ? $latestOdontogram->odontogram_data : new \stdClass,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePatientRequest $request, Patient $patient)
    {
        $patient->update($request->validated());

        return redirect()->back()->with('success', 'Patient updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Patient $patient)
    {
        $patient->delete();

        return redirect()->back()->with('success', 'Paciente eliminado exitosamente.');
    }

    public function downloadHistoria(Patient $patient, PdfGeneratorService $pdfService)
    {
        return $pdfService->generarHistoriaClinica($patient)->download();
    }

    public function downloadContrato(Request $request, Patient $patient, PdfGeneratorService $pdfService)
    {
        $plantilla = $request->input('plantilla', 'contrato');

        return $pdfService->generarPlantilla($patient, $plantilla)->inline("{$plantilla}_{$patient->id}.pdf");
    }

    public function downloadOdontograma(Request $request, Patient $patient, PdfGeneratorService $pdfService)
    {
        $request->validate(['html' => 'required|string']);
        $pdf = $pdfService->generarOdontograma($patient, $request->html);

        return response()->json([
            'base64' => $pdf->base64(),
        ]);
    }
}
