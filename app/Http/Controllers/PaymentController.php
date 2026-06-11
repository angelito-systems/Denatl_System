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
    public function store(StorePaymentRequest $request, \App\Services\SunatService $sunatService)
    {
        $payment = Payment::create($request->validated());

        $sunatStatus = '';
        if (in_array($payment->receipt_type, ['Boleta', 'Factura'])) {
            try {
                $sunatService->emitirComprobante($payment);
                $sunatStatus = ' y emitido a SUNAT correctamente';
            } catch (\Exception $e) {
                $sunatStatus = ' pero hubo un error con SUNAT: ' . $e->getMessage();
            }
        }

        return redirect()->back()->with([
            'success' => 'Pago registrado' . $sunatStatus . '.',
            'new_payment_id' => $payment->id,
            'auto_print' => true
        ]);
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
        return $pdfService->generarComprobante($payment)->inline();
    }

    public function emitirSunat(Request $request, Payment $payment, \App\Services\SunatService $sunatService)
    {
        try {
            // Permitir actualizar datos de facturación (RUC, Razón Social) antes de emitir
            if ($request->has('billing_document') || $request->has('billing_name')) {
                $payment->update([
                    'billing_document' => $request->input('billing_document'),
                    'billing_name' => $request->input('billing_name')
                ]);
            }

            $sunatService->emitirComprobante($payment);
            return redirect()->back()->with('success', 'Comprobante emitido y aceptado por SUNAT exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error SUNAT: ' . $e->getMessage());
        }
    }

    public function downloadSunat(Payment $payment, $type)
    {
        if ($type === 'xml' && $payment->sunat_xml_path) {
            return \Illuminate\Support\Facades\Storage::disk('public')->download($payment->sunat_xml_path);
        }
        
        if ($type === 'cdr' && $payment->sunat_cdr_path) {
            return \Illuminate\Support\Facades\Storage::disk('public')->download($payment->sunat_cdr_path);
        }

        abort(404, 'Archivo no encontrado');
    }
}
