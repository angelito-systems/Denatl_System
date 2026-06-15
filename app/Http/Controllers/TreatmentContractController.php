<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\TreatmentContract;
use Illuminate\Http\Request;

class TreatmentContractController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'treatment_name' => 'required|string|max:255',
            'total_cost' => 'required|numeric|min:0',
            'down_payment' => 'nullable|numeric|min:0',
            'installments' => 'nullable|integer|min:1',
            'installment_amount' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'status' => 'nullable|string',
        ]);

        if (empty($validated['status'])) {
            $validated['status'] = 'Activo';
        }

        $contract = TreatmentContract::create($validated);

        // Crear documento asociado para la firma
        $document = Document::create([
            'patient_id' => $contract->patient_id,
            'type' => 'financiamiento',
            'name' => 'Contrato de '.$contract->treatment_name,
            'status' => 'Borrador',
        ]);

        $contract->update(['document_id' => $document->id]);

        return redirect()->back()->with('success', 'Contrato de financiamiento creado.');
    }

    public function show(TreatmentContract $treatment_contract)
    {
        $treatment_contract->load(['patient', 'payments', 'document.media']);

        return inertia('TreatmentContracts/Show', [
            'contract' => $treatment_contract,
        ]);
    }

    public function update(Request $request, TreatmentContract $treatment_contract)
    {
        $validated = $request->validate([
            'treatment_name' => 'sometimes|required|string|max:255',
            'total_cost' => 'sometimes|required|numeric|min:0',
            'down_payment' => 'nullable|numeric|min:0',
            'installments' => 'nullable|integer|min:1',
            'installment_amount' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'status' => 'nullable|string',
        ]);

        $treatment_contract->update($validated);

        return redirect()->back()->with('success', 'Contrato actualizado.');
    }

    public function destroy(TreatmentContract $treatment_contract)
    {
        if ($treatment_contract->document_id) {
            $document = Document::find($treatment_contract->document_id);
            if ($document) {
                // Delete media if using spatie medialibrary (handled by Document model usually, but soft deletes might keep them)
                $document->delete();
            }
        }
        $treatment_contract->delete();

        return redirect()->back()->with('success', 'Contrato y documento eliminado.');
    }
}
