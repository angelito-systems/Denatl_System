<?php

namespace App\Http\Controllers;

use App\Models\Cashbox;
use App\Models\CashboxTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CashboxController extends Controller
{
    public function index()
    {
        $currentCashbox = Cashbox::where('status', 'open')->latest()->first();
        
        $transactions = [];
        $totals = ['income' => 0, 'expense' => 0];

        if ($currentCashbox) {
            $transactions = CashboxTransaction::where('cashbox_id', $currentCashbox->id)
                ->with('user:id,first_name,last_name')
                ->latest()
                ->get();

            $totals['income'] = $transactions->where('type', 'income')->sum('amount');
            $totals['expense'] = $transactions->where('type', 'expense')->sum('amount');
        }

        $recentCashboxes = Cashbox::where('status', 'closed')
            ->with(['openedBy:id,first_name,last_name', 'closedBy:id,first_name,last_name'])
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Cashbox/Index', [
            'currentCashbox' => $currentCashbox,
            'transactions' => $transactions,
            'totals' => $totals,
            'recentCashboxes' => $recentCashboxes
        ]);
    }

    public function open(Request $request)
    {
        $request->validate([
            'opening_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        if (Cashbox::where('status', 'open')->exists()) {
            return redirect()->back()->with('error', 'Ya existe una caja abierta.');
        }

        Cashbox::create([
            'opening_amount' => $request->opening_amount,
            'status' => 'open',
            'opened_at' => Carbon::now(),
            'opened_by_id' => auth()->id(),
            'notes' => $request->notes,
        ]);

        return redirect()->back()->with('success', 'Caja abierta correctamente.');
    }

    public function close(Request $request, Cashbox $cashbox)
    {
        if ($cashbox->status === 'closed') {
            return redirect()->back()->with('error', 'La caja ya está cerrada.');
        }

        $transactions = CashboxTransaction::where('cashbox_id', $cashbox->id)->get();
        $income = $transactions->where('type', 'income')->sum('amount');
        $expense = $transactions->where('type', 'expense')->sum('amount');
        
        $expectedAmount = $cashbox->opening_amount + $income - $expense;

        $cashbox->update([
            'status' => 'closed',
            'closing_amount' => $expectedAmount, // Opcional: permitir al usuario ingresar el monto físico real para ver descuadres
            'closed_at' => Carbon::now(),
            'closed_by_id' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Caja cerrada correctamente.');
    }

    public function storeTransaction(Request $request, Cashbox $cashbox)
    {
        $request->validate([
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'description' => 'required|string'
        ]);

        if ($cashbox->status === 'closed') {
            return redirect()->back()->with('error', 'No se pueden añadir transacciones a una caja cerrada.');
        }

        CashboxTransaction::create([
            'cashbox_id' => $cashbox->id,
            'type' => $request->type,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'description' => $request->description,
            'user_id' => auth()->id()
        ]);

        return redirect()->back()->with('success', 'Transacción guardada correctamente.');
    }
}
