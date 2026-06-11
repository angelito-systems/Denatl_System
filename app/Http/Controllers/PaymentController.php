<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use Inertia\Inertia;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Payment::with('patient:id,first_name,last_name,dni');

        if ($request->has('search') && $request->input('search') !== '') {
            $search = $request->input('search');
            $query->whereHas('patient', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('dni', 'like', "%{$search}%");
            })->orWhere('notes', 'like', "%{$search}%");
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        $patients = \App\Models\Patient::select('id', 'first_name', 'last_name', 'dni', 'phone')->get();

        return Inertia::render('Payments/Index', [
            'payments' => $payments,
            'patients' => $patients,
            'filters' => $request->only(['search'])
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePaymentRequest $request)
    {
        Payment::create($request->validated());
        return redirect()->back()->with('success', 'Pago registrado exitosamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePaymentRequest $request, Payment $payment)
    {
        $payment->update($request->validated());
        return redirect()->back()->with('success', 'Pago actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment)
    {
        $payment->delete();
        return redirect()->back()->with('success', 'Pago eliminado exitosamente.');
    }

    public function downloadComprobante(Payment $payment, \App\Services\PdfGeneratorService $pdfService)
    {
        return $pdfService->generarComprobante($payment)->download();
    }
}
