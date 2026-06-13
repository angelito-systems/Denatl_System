<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Patient;
use App\Models\Payment;
use App\Services\PdfGeneratorService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MessageController extends Controller
{
    public function index()
    {
        $conversations = Conversation::with(['patient', 'messages' => function ($q) {
            $q->orderBy('created_at', 'asc');
        }])->orderBy('last_message_at', 'desc')->get();

        return inertia('Messages/Index', [
            'conversations' => $conversations,
        ]);
    }

    public function send(Request $request, WhatsAppService $whatsapp)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        $success = $whatsapp->enviarMensaje($validated['phone'], $validated['message']);

        if ($success) {
            return redirect()->back()->with('success', 'Mensaje enviado exitosamente');
        }

        return redirect()->back()->with('error', 'Error al enviar el mensaje');
    }

    public function toggleBot(Request $request, Conversation $conversation, WhatsAppService $whatsapp)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,human_assigned',
        ]);

        $conversation->update(['bot_status' => $validated['status']]);

        if ($validated['status'] === 'human_assigned') {
            $msg = '👨‍💼 Un asesor se ha unido a la conversación. En breve te atenderemos.';
            $whatsapp->enviarMensaje($conversation->phone_number, $msg);
        } else {
            $msg = '🤖 ¡Hola! Soy el bot nuevamente. Estoy de vuelta para ayudarte en lo que necesites.';
            $whatsapp->enviarMensaje($conversation->phone_number, $msg);
        }

        return redirect()->back()->with('success', 'Estado del bot actualizado');
    }

    public function sendDocument(Request $request, WhatsAppService $whatsapp, PdfGeneratorService $pdfService)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'media_id' => 'nullable|integer|exists:media,id',
            'payment_id' => 'nullable|integer|exists:payments,id',
            'patient_id' => 'nullable|integer|exists:patients,id',
            'type' => 'nullable|string',
            'caption' => 'nullable|string',
            'plantilla' => 'nullable|string', // For contratos
        ]);

        $base64 = null;
        $fileName = 'documento.pdf';

        if (! empty($validated['media_id'])) {
            $media = Media::findOrFail($validated['media_id']);
            $base64 = base64_encode(file_get_contents($media->getPath()));
            $fileName = $media->file_name;
        } elseif (! empty($validated['payment_id'])) {
            $payment = Payment::with('patient')->findOrFail($validated['payment_id']);
            $pdf = $pdfService->generarComprobante($payment);
            $base64 = $pdf->base64();
            $fileName = "comprobante_{$payment->id}.pdf";
        } elseif (! empty($validated['patient_id'])) {
            $patient = Patient::findOrFail($validated['patient_id']);
            if ($validated['type'] === 'historia') {
                $pdf = $pdfService->generarHistoriaClinica($patient);
                $base64 = $pdf->base64();
                $fileName = "historia_clinica_{$patient->id}.pdf";
            } elseif ($validated['type'] === 'contrato') {
                $plantilla = $validated['plantilla'] ?? 'contrato';
                $pdf = $pdfService->generarPlantilla($patient, $plantilla);
                $base64 = $pdf->base64();
                $fileName = "{$plantilla}_{$patient->id}.pdf";
            } else {
                return redirect()->back()->with('error', 'Tipo de documento no soportado.');
            }
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

    public function clear(Conversation $conversation)
    {
        $conversation->messages()->delete();
        $conversation->update(['last_message_at' => now()]);

        return redirect()->back()->with('success', 'Historial del chat vaciado permanentemente.');
    }
}
