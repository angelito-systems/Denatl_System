<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\WhatsAppService;

class MessageController extends Controller
{
    public function index()
    {
        return Inertia::render('Messages/Index');
    }

    public function send(Request $request, WhatsAppService $whatsapp)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string'
        ]);

        $success = $whatsapp->enviarMensaje($validated['phone'], $validated['message']);

        if ($success) {
            return redirect()->back()->with('success', 'Mensaje enviado exitosamente');
        }

        return redirect()->back()->with('error', 'Error al enviar el mensaje');
    }

    public function sendDocument(Request $request, WhatsAppService $whatsapp, \App\Services\PdfGeneratorService $pdfService)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'media_id' => 'nullable|integer|exists:media,id',
            'payment_id' => 'nullable|integer|exists:payments,id',
            'caption' => 'nullable|string'
        ]);

        $base64 = null;
        $fileName = 'documento.pdf';

        if (!empty($validated['media_id'])) {
            $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::findOrFail($validated['media_id']);
            $base64 = base64_encode(file_get_contents($media->getPath()));
            $fileName = $media->file_name;
        } elseif (!empty($validated['payment_id'])) {
            $payment = \App\Models\Payment::with('patient')->findOrFail($validated['payment_id']);
            $pdf = $pdfService->generarComprobante($payment);
            $base64 = $pdf->base64();
            $fileName = "comprobante_{$payment->id}.pdf";
        } else {
            return redirect()->back()->with('error', 'No se especificó un documento válido.');
        }

        $caption = $validated['caption'] ?? 'Aquí tienes tu documento.';
        $success = $whatsapp->enviarDocumento($validated['phone'], $base64, $fileName, $caption);

        if ($success) {
            return redirect()->back()->with('success', 'Documento enviado exitosamente por WhatsApp.');
        }

        return redirect()->back()->with('error', 'Error al enviar el documento por WhatsApp.');
    }
}
