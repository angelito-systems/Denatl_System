<?php

namespace App\Http\Controllers;

use App\Jobs\NotifyRaffleWinners;
use App\Models\Raffle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RaffleController extends Controller
{
    public function index()
    {
        $raffles = Raffle::withCount('participants')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Raffles/Index', [
            'raffles' => $raffles,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,active,completed',
            'draw_date' => 'nullable|date',
            'winner_count' => 'required|integer|min:1',
            'winning_logic' => 'required|in:random,first,nth',
            'winning_nth_position' => 'nullable|integer|min:1',
            'prizes' => 'array',
            'prizes.*.name' => 'required|string',
            'prizes.*.position' => 'required|integer|min:1',
        ]);

        $raffle = Raffle::create($validated);

        if (! empty($validated['prizes'])) {
            foreach ($validated['prizes'] as $prizeData) {
                $raffle->prizes()->create($prizeData);
            }
        }

        return redirect()->route('raffles.index')->with('success', 'Sorteo creado exitosamente');
    }

    public function show(Raffle $raffle)
    {
        $raffle->load(['prizes', 'participants.patient', 'winners.patient', 'winners.prize']);

        return Inertia::render('Raffles/Show', [
            'raffle' => $raffle,
        ]);
    }

    public function update(Request $request, Raffle $raffle)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,active,completed',
            'draw_date' => 'nullable|date',
            'winner_count' => 'required|integer|min:1',
            'winning_logic' => 'required|in:random,first,nth',
            'winning_nth_position' => 'nullable|integer|min:1',
            'prizes' => 'array',
        ]);

        $raffle->update($validated);

        // Actualizar premios (simplificado: borra y crea)
        if (isset($validated['prizes'])) {
            $raffle->prizes()->delete();
            foreach ($validated['prizes'] as $prizeData) {
                $raffle->prizes()->create([
                    'name' => $prizeData['name'],
                    'position' => $prizeData['position'],
                ]);
            }
        }

        return redirect()->back()->with('success', 'Sorteo actualizado');
    }

    public function destroy(Raffle $raffle)
    {
        $raffle->delete();

        return redirect()->route('raffles.index')->with('success', 'Sorteo eliminado');
    }

    public function draw(Raffle $raffle)
    {
        if ($raffle->status === 'completed') {
            return redirect()->back()->with('error', 'El sorteo ya fue realizado');
        }

        $participants = $raffle->participants()->orderBy('created_at', 'asc')->get();

        if ($participants->isEmpty()) {
            return redirect()->back()->with('error', 'No hay participantes para el sorteo');
        }

        $winners = collect();
        $winnerCount = min($raffle->winner_count, $participants->count());

        if ($raffle->winning_logic === 'random') {
            $winners = $participants->random($winnerCount);
        } elseif ($raffle->winning_logic === 'first') {
            $winners = $participants->take($winnerCount);
        } elseif ($raffle->winning_logic === 'nth') {
            $pos = $raffle->winning_nth_position;
            if ($pos && $pos <= $participants->count()) {
                $winners->push($participants[$pos - 1]);
            } else {
                // Fallback a random si la posicion no existe
                $winners = $participants->random($winnerCount);
            }
        }

        $prizes = $raffle->prizes()->orderBy('position', 'asc')->get();
        $prizeIndex = 0;

        foreach ($winners as $winner) {
            $prizeId = null;
            if ($prizes->count() > 0) {
                // Asignar el premio disponible en orden, o el último si se acaban
                $prizeId = $prizes[$prizeIndex]?->id ?? $prizes->last()->id;
                if ($prizeIndex < $prizes->count() - 1) {
                    $prizeIndex++;
                }
            }

            $winner->update([
                'is_winner' => true,
                'raffle_prize_id' => $prizeId,
            ]);
        }

        $raffle->update([
            'status' => 'completed',
        ]);

        // Disparar Job para notificar
        NotifyRaffleWinners::dispatch($raffle->id);

        return redirect()->back()->with('success', '¡Sorteo realizado con éxito!');
    }
}
