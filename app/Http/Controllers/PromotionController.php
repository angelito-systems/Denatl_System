<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PromotionController extends Controller
{
    public function index()
    {
        $promotions = Promotion::orderBy('created_at', 'desc')->get();
        return Inertia::render('Promotions/Index', [
            'promotions' => $promotions
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean'
        ]);

        Promotion::create($validated);

        return redirect()->back()->with('success', 'Promoción creada exitosamente');
    }

    public function update(Request $request, string $id)
    {
        $promotion = Promotion::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean'
        ]);

        $promotion->update($validated);

        return redirect()->back()->with('success', 'Promoción actualizada exitosamente');
    }

    public function destroy(string $id)
    {
        $promotion = Promotion::findOrFail($id);
        $promotion->delete();

        return redirect()->back()->with('success', 'Promoción eliminada exitosamente');
    }
}
