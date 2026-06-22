<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::orderBy('created_at', 'desc')->get();

        return Inertia::render('Campaigns/Index', [
            'campaigns' => $campaigns,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:Encendida,Apagada',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'message' => 'required|string',
            'target_audience' => 'nullable|string',
            'send_time' => 'nullable|date_format:H:i',
            'frequency' => 'nullable|string',
            'channel' => 'required|string',
        ]);

        Campaign::create($validated);

        return back()->with('success', 'Campaña creada exitosamente');
    }

    public function update(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:Encendida,Apagada',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'message' => 'required|string',
            'target_audience' => 'nullable|string',
            'send_time' => 'nullable|date_format:H:i', // En Laravel el validate de date_format:H:i o date_format:H:i:s puede variar, usar H:i si el input es HH:MM
            'frequency' => 'nullable|string',
            'channel' => 'required|string',
        ]);

        $campaign->update($validated);

        return back()->with('success', 'Campaña actualizada exitosamente');
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();

        return back()->with('success', 'Campaña eliminada exitosamente');
    }
}
