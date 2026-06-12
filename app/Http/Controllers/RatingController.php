<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RatingController extends Controller
{
    public function index()
    {
        $ratings = Rating::with('patient')->orderBy('created_at', 'desc')->get();
        return Inertia::render('Ratings/Index', [
            'ratings' => $ratings
        ]);
    }

    public function destroy(string $id)
    {
        $rating = Rating::findOrFail($id);
        $rating->delete();

        return redirect()->back()->with('success', 'Valoración eliminada exitosamente');
    }
}
