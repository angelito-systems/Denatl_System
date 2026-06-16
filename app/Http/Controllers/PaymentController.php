<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\Cashbox;
use App\Models\CashboxTransaction;
use App\Models\Configuration;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Treatment;
use App\Models\TreatmentContract;
use App\Services\PdfGeneratorService;
use App\Services\SunatService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

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
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('dni', 'like', "%{$search}%");
            })->orWhere('notes', 'like', "%{$search}%");
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $patients = Patient::with(['treatmentContracts' => function ($q) {
            $q->where('status', '!=', 'Finalizado');
        }])->select('id', 'first_name', 'last_name', 'dni', 'phone')->get();
        $treatments = Treatment::orderBy('name')->get(['id', 'name', 'base_price']);

        return Inertia::render('Payments/Index', [
            'payments' => $payments,
            'patients' => $patients,
            'treatments' => $treatments,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePaymentRequest $request, SunatService $sunatService, PdfGeneratorService $pdfService, WhatsAppService $whatsappService)
    {
        $validatedData = $request->validated();
        $isSunatActive = Configuration::get('sunat_active') === '1';

        // If SUNAT is inactive, force the receipt type to Ticket regardless of the input
        if (! $isSunatActive && in_array($validatedData['receipt_type'] ?? '', ['Boleta', 'Factura'])) {
            $validatedData['receipt_type'] = 'Ticket';
        }

        $payment = Payment::create($validatedData);
        $payment->load(['patient', 'treatmentContract']);

        // Update contract balance if applicable
        if ($payment->treatment_contract_id) {
            $contract = TreatmentContract::find($payment->treatment_contract_id);
            if ($contract && $contract->total_paid >= $contract->total_cost) {
                $contract->update(['status' => 'Finalizado']);
            }
        }

        // Registrar en Caja (si hay una abierta)
        $cashbox = Cashbox::where('status', 'open')->latest()->first();
        if ($cashbox) {
            CashboxTransaction::create([
                'cashbox_id' => $cashbox->id,
                'type' => 'income',
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'description' => 'Pago de cuota/tratamiento - '.($payment->patient->first_name ?? '').' '.($payment->patient->last_name ?? ''),
                'related_model_type' => get_class($payment),
                'related_model_id' => $payment->id,
                'user_id' => auth()->id() ?? 1,
            ]);
        }

        $sunatStatus = '';
        if (in_array($payment->receipt_type, ['Boleta', 'Factura']) && $isSunatActive) {
            try {
                $sunatService->emitirComprobante($payment);
                $sunatStatus = ' y emitido a SUNAT correctamente';
            } catch (\Exception $e) {
                $sunatStatus = ' pero hubo un error con SUNAT: '.$e->getMessage();
            }
        }

        // WhatsApp notification with PDF ticket
        if ($payment->patient && $payment->patient->phone) {
            try {
                $pdf = $pdfService->generarComprobante($payment);

                // Save to temp file to get base64
                $tmpPath = tempnam(sys_get_temp_dir(), 'pdf_').'.pdf';
                $pdf->save($tmpPath);
                $base64 = base64_encode(file_get_contents($tmpPath));
                @unlink($tmpPath);

                $tratamiento = $payment->treatmentContract ? $payment->treatmentContract->treatment_name : 'atención dental';
                $saldo = $payment->treatmentContract ? number_format($payment->treatmentContract->balance_due, 2) : '0.00';

                $mensaje = "Hola {$payment->patient->first_name}, se registró tu pago de S/ ".number_format($payment->amount, 2)." para tu tratamiento de {$tratamiento}.\n";
                if ($payment->treatment_contract_id) {
                    $mensaje .= "Saldo pendiente: S/ {$saldo}.\n";
                }
                $mensaje .= 'Adjuntamos tu comprobante. Gracias por confiar en nosotros 🦷';

                $whatsappService->enviarDocumento(
                    $payment->patient->phone,
                    $base64,
                    "Comprobante_{$payment->id}.pdf",
                    $mensaje
                );
            } catch (\Exception $e) {
                Log::error('Error enviando ticket por WhatsApp: '.$e->getMessage());
            }
        }

        return redirect()->back()->with([
            'success' => 'Pago registrado'.$sunatStatus.'.',
            'new_payment_id' => $payment->id,
            'auto_print' => true,
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

    public function downloadComprobante(Payment $payment, PdfGeneratorService $pdfService)
    {
        return $pdfService->generarComprobante($payment)->inline();
    }

    public function comprobanteBase64(Payment $payment, PdfGeneratorService $pdfService)
    {
        $pdf = $pdfService->generarComprobante($payment);

        // Save to a temp file, read it, return as base64
        $tmpPath = tempnam(sys_get_temp_dir(), 'pdf_').'.pdf';
        $pdf->save($tmpPath);
        $base64 = base64_encode(file_get_contents($tmpPath));
        @unlink($tmpPath);

        return response()->json(['base64' => $base64]);
    }

    public function emitirSunat(Request $request, Payment $payment, SunatService $sunatService)
    {
        $isSunatActive = Configuration::get('sunat_active') === '1';
        if (! $isSunatActive) {
            return redirect()->back()->with('error', 'El sistema está configurado para emitir solo Tickets Internos. Habilite SUNAT en Configuración primero.');
        }

        try {
            // Permitir actualizar datos de facturación (RUC, Razón Social) antes de emitir
            if ($request->has('billing_document') || $request->has('billing_name')) {
                $payment->update([
                    'billing_document' => $request->input('billing_document'),
                    'billing_name' => $request->input('billing_name'),
                ]);
            }

            $sunatService->emitirComprobante($payment);

            return redirect()->back()->with('success', 'Comprobante emitido y aceptado por SUNAT exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error SUNAT: '.$e->getMessage());
        }
    }

    public function downloadSunat(Payment $payment, $type)
    {
        if ($type === 'xml' && $payment->sunat_xml_path) {
            return Storage::disk('public')->download($payment->sunat_xml_path);
        }

        if ($type === 'cdr' && $payment->sunat_cdr_path) {
            return Storage::disk('public')->download($payment->sunat_cdr_path);
        }

        abort(404, 'Archivo no encontrado');
    }
}
