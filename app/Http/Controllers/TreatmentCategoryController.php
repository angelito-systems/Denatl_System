<?php

namespace App\Http\Controllers;

use App\Models\TreatmentCategory;
use Illuminate\Http\Request;

class TreatmentCategoryController extends Controller
{
    public function index()
    {
        $categories = TreatmentCategory::orderBy('name')->get();

        return inertia('TreatmentCategories/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:treatment_categories,name',
        ]);
        TreatmentCategory::create($validated);

        return redirect()->back()->with('success', 'Categoría creada exitosamente.');
    }

    public function update(Request $request, TreatmentCategory $treatmentCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:treatment_categories,name,'.$treatmentCategory->id,
        ]);
        $treatmentCategory->update($validated);

        return redirect()->back()->with('success', 'Categoría actualizada exitosamente.');
    }

    public function destroy(TreatmentCategory $treatmentCategory)
    {
        if ($treatmentCategory->treatments()->count() > 0) {
            return redirect()->back()->with('error', 'No se puede eliminar la categoría porque tiene tratamientos asociados.');
        }
        $treatmentCategory->delete();

        return redirect()->back()->with('success', 'Categoría eliminada exitosamente.');
    }
}
