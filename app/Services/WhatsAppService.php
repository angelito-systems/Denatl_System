<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Configuration;
use App\Models\Conversation;
use App\Models\WhatsappMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $apiUrl;

    private string $apiKey;

    private string $instance;

    public function __construct()
    {
        $this->apiUrl = rtrim(Configuration::get('whatsapp_api_url', env('EVOLUTION_API_URL', 'http://localhost:8080')), '/');
        $this->apiKey = Configuration::get('whatsapp_api_key', env('EVOLUTION_API_KEY', 'my-global-api-key'));
        $this->instance = Configuration::get('whatsapp_instance', env('EVOLUTION_INSTANCE', 'clinica-dental'));
    }

    /**
     * Enviar mensaje de texto a través de Evolution API
     */
    public function enviarMensaje(string $telefono, string $mensaje): bool
    {
        try {
            $url = "{$this->apiUrl}/message/sendText/{$this->instance}";

            // Format phone number to remove + and spaces
            $telefono = preg_replace('/[^0-9]/', '', $telefono);

            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'number' => $telefono,
                'text' => $mensaje,
            ]);

            if ($response->successful()) {
                Log::info("WhatsApp enviado a {$telefono}: {$mensaje}");

                $responseData = $response->json();
                $messageId = $responseData['key']['id'] ?? 'OUT_'.uniqid();

                $conversation = Conversation::where('phone_number', $telefono)->first();
                if ($conversation) {
                    WhatsappMessage::create([
                        'conversation_id' => $conversation->id,
                        'remote_jid' => "{$telefono}@s.whatsapp.net",
                        'sender_type' => 'advisor',
                        'content' => $mensaje,
                        'type' => 'text',
                        'read_status' => true,
                        'message_id' => $messageId,
                    ]);
                    $conversation->update(['last_message_at' => now()]);
                }

                return true;
            }

            Log::error('Error Evolution API: '.$response->body());

            return false;

        } catch (\Exception $e) {
            Log::error('Excepción Evolution API: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Enviar recordatorio de cita a un paciente
     */
    public function enviarRecordatorioCita(Appointment $cita): void
    {
        if (! $cita->patient || empty($cita->patient->phone)) {
            Log::warning("No se pudo enviar recordatorio: Paciente sin número telefónico para cita {$cita->id}");

            return;
        }

        $clinica = 'Clínica Dental System';
        $fecha = Carbon::parse($cita->date)->format('d/m/Y');
        $hora = Carbon::parse($cita->start_time)->format('H:i');

        $mensaje = "¡Hola {$cita->patient->first_name}! 👋\n\n";
        $mensaje .= "Te escribimos de *{$clinica}* para recordarte tu próxima cita:\n\n";
        $mensaje .= "📅 *Fecha:* {$fecha}\n";
        $mensaje .= "⏰ *Hora:* {$hora}\n";
        $mensaje .= "🦷 *Tratamiento:* {$cita->treatment}\n\n";
        $mensaje .= "Por favor, confírmanos tu asistencia respondiendo a este mensaje con un 'SÍ' o avísanos si deseas reprogramar.\n\n";
        $mensaje .= '¡Te esperamos!';

        $this->enviarMensaje($cita->patient->phone, $mensaje);
    }

    /**
     * Enviar notificación de cita confirmada
     */
    public function enviarConfirmacionCita(Appointment $cita): void
    {
        if (! $cita->patient || empty($cita->patient->phone)) {
            return;
        }

        $clinica = 'Clínica Dental System';
        $fecha = Carbon::parse($cita->date)->format('d/m/Y');
        $hora = Carbon::parse($cita->start_time)->format('H:i');

        $mensaje = "¡Hola {$cita->patient->first_name}! ✅\n\n";
        $mensaje .= "Tu cita en *{$clinica}* ha sido *CONFIRMADA*.\n\n";
        $mensaje .= "📅 *Fecha:* {$fecha}\n";
        $mensaje .= "⏰ *Hora:* {$hora}\n";
        $mensaje .= "🦷 *Tratamiento:* {$cita->treatment}\n\n";
        $mensaje .= '¡Nos vemos pronto!';

        $this->enviarMensaje($cita->patient->phone, $mensaje);
    }

    /**
     * Enviar notificación de cita cancelada
     */
    public function enviarCancelacionCita(Appointment $cita): void
    {
        if (! $cita->patient || empty($cita->patient->phone)) {
            return;
        }

        $clinica = 'Clínica Dental System';

        $mensaje = "¡Hola {$cita->patient->first_name}! ❌\n\n";
        $mensaje .= "Te informamos que tu cita para el tratamiento de *{$cita->treatment}* en *{$clinica}* ha sido *CANCELADA*.\n\n";
        $mensaje .= 'Si deseas reprogramar, por favor contáctanos respondiendo a este mensaje.';

        $this->enviarMensaje($cita->patient->phone, $mensaje);
    }

    /**
     * Enviar notificación de cita reprogramada
     */
    public function enviarReprogramacionCita(Appointment $cita): void
    {
        if (! $cita->patient || empty($cita->patient->phone)) {
            return;
        }

        $clinica = 'Clínica Dental System';
        $fecha = Carbon::parse($cita->date)->format('d/m/Y');
        $hora = Carbon::parse($cita->start_time)->format('H:i');

        $mensaje = "¡Hola {$cita->patient->first_name}! 🔄\n\n";
        $mensaje .= "Tu cita en *{$clinica}* ha sido *REPROGRAMADA*. Estos son los nuevos datos:\n\n";
        $mensaje .= "📅 *Nueva Fecha:* {$fecha}\n";
        $mensaje .= "⏰ *Nueva Hora:* {$hora}\n";
        $mensaje .= "🦷 *Tratamiento:* {$cita->treatment}\n\n";
        $mensaje .= 'Si tienes algún inconveniente, por favor avísanos.';

        $this->enviarMensaje($cita->patient->phone, $mensaje);
    }

    /**
     * Enviar documento o media a través de Evolution API usando Base64
     */
    public function enviarDocumento(string $telefono, string $base64, string $fileName, string $caption = ''): bool
    {
        try {
            $url = "{$this->apiUrl}/message/sendMedia/{$this->instance}";

            // Format phone number to remove + and spaces
            $telefono = preg_replace('/[^0-9]/', '', $telefono);

            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'number' => $telefono,
                'mediatype' => 'document',
                'caption' => $caption,
                'media' => $base64,
                'fileName' => $fileName,
            ]);

            if ($response->successful()) {
                Log::info("Documento WhatsApp enviado a {$telefono}: {$fileName}");

                return true;
            }

            Log::error('Error Evolution API Documento: '.$response->body());

            return false;

        } catch (\Exception $e) {
            Log::error('Error al enviar mensaje por WhatsApp (Exception): ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Enviar documento o imagen en Base64
     */
    public function enviarMediaBase64(string $telefono, string $base64, string $mimetype, string $fileName, string $mensaje = ''): bool
    {
        try {
            $url = "{$this->apiUrl}/message/sendMedia/{$this->instance}";
            $telefono = preg_replace('/[^0-9]/', '', $telefono);

            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'number' => $telefono,
                'mediaMessage' => [
                    'mediatype' => 'document',
                    'caption' => $mensaje,
                    'media' => $base64,
                    'fileName' => $fileName,
                ],
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Error al enviar media por WhatsApp (Exception): ' . $e->getMessage());
            return false;
        }
    }
}
