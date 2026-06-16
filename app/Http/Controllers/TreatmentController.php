<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTreatmentRequest;
use App\Http\Requests\UpdateTreatmentRequest;
use App\Models\Treatment;
use App\Models\TreatmentCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TreatmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Treatment::with('treatmentCategory');
        if ($request->has('search') && $request->input('search') !== '') {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%")
                ->orWhereHas('treatmentCategory', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
        }

        $treatments = $query->orderBy('name')->paginate(15)->withQueryString();
        $categories = TreatmentCategory::orderBy('name')->get();

        return Inertia::render('Treatments/Index', [
            'treatments' => $treatments,
            'categories' => $categories,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTreatmentRequest $request)
    {
        Treatment::create($request->validated());

        return redirect()->back()->with('success', 'Tratamiento creado exitosamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTreatmentRequest $request, Treatment $treatment)
    {
        $treatment->update($request->validated());

        return redirect()->back()->with('success', 'Tratamiento actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Treatment $treatment)
    {
        $treatment->delete();

        return redirect()->back()->with('success', 'Tratamiento eliminado exitosamente.');
    }
}
