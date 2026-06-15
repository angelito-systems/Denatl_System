<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Conversation;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\TreatmentContract;
use App\Services\PdfGeneratorService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MessageController extends Controller
{
    public function index($id = null)
    {
        $conversations = Conversation::with(['patient', 'messages' => function ($q) {
            $q->orderBy('created_at', 'asc');
        }])->orderBy('last_message_at', 'desc')->get();

        return inertia('Messages/Index', [
            'conversations' => $conversations,
            'selectedId' => $id ? (int) $id : null,
        ]);
    }

    public function send(Request $request, WhatsAppService $whatsapp)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'message' => 'nullable|string',
            'attachment' => 'nullable|file|max:5120', // Max 5MB
        ]);

        $message = $validated['message'] ?? '';

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $base64 = base64_encode(file_get_contents($file->getRealPath()));
            $mime = $file->getMimeType();
            $fileName = $file->getClientOriginalName();

            $success = $whatsapp->enviarMediaBase64($validated['phone'], $base64, $mime, $fileName, $message);
        } else {
            $success = $whatsapp->enviarMensaje($validated['phone'], $message);
        }

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
            'contract_id' => 'nullable|integer|exists:treatment_contracts,id',
            'appointment_id' => 'nullable|integer|exists:appointments,id',
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
        } elseif (! empty($validated['contract_id'])) {
            $contract = TreatmentContract::with('patient')->findOrFail($validated['contract_id']);
            $pdf = $pdfService->generarPlantilla($contract->patient, 'contrato');
            $base64 = $pdf->base64();
            $fileName = "contrato_{$contract->id}.pdf";
        } elseif (! empty($validated['appointment_id'])) {
            $cita = Appointment::with('patient')->findOrFail($validated['appointment_id']);
            $whatsapp->enviarRecordatorioCita($cita);

            return redirect()->back()->with('success', 'Recordatorio de cita enviado exitosamente por WhatsApp.');
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
            } elseif ($validated['type'] === 'ultimo_comprobante') {
                $payment = Payment::with('patient')->where('patient_id', $patient->id)->orderBy('id', 'desc')->first();
                if (! $payment) {
                    return redirect()->back()->with('error', 'El paciente no tiene comprobantes recientes.');
                }
                $pdf = $pdfService->generarComprobante($payment);
                $base64 = $pdf->base64();
                $fileName = "comprobante_{$payment->id}.pdf";
                if (empty($caption)) {
                    $caption = 'Aquí tienes tu último comprobante de pago.';
                }
            } elseif ($validated['type'] === 'proxima_cita') {
                $cita = Appointment::where('patient_id', $patient->id)
                    ->whereDate('date', '>=', now())
                    ->orderBy('date')->orderBy('start_time')
                    ->first();
                if (! $cita) {
                    return redirect()->back()->with('error', 'El paciente no tiene próximas citas programadas.');
                }
                $whatsapp->enviarRecordatorioCita($cita);

                return redirect()->back()->with('success', 'Recordatorio de cita enviado exitosamente por WhatsApp.');
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
