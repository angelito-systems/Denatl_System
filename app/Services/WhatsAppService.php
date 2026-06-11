<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $apiUrl;
    private string $apiKey;
    private string $instance;

    public function __construct()
    {
        $this->apiUrl = config('services.evolution.api_url', env('EVOLUTION_API_URL', 'http://localhost:8080'));
        $this->apiKey = config('services.evolution.api_key', env('EVOLUTION_API_KEY', 'my-global-api-key'));
        $this->instance = config('services.evolution.instance', env('EVOLUTION_INSTANCE', 'clinica-dental'));
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
                'Content-Type' => 'application/json'
            ])->post($url, [
                'number' => $telefono,
                'text' => $mensaje
            ]);

            if ($response->successful()) {
                Log::info("WhatsApp enviado a {$telefono}: {$mensaje}");
                return true;
            }

            Log::error("Error Evolution API: " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error("Excepción Evolution API: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar recordatorio de cita a un paciente
     */
    public function enviarRecordatorioCita(Appointment $cita): void
    {
        if (!$cita->patient || empty($cita->patient->phone)) {
            Log::warning("No se pudo enviar recordatorio: Paciente sin número telefónico para cita {$cita->id}");
            return;
        }

        $clinica = "Clínica Dental System";
        $fecha = \Carbon\Carbon::parse($cita->date)->format('d/m/Y');
        $hora = \Carbon\Carbon::parse($cita->start_time)->format('H:i');
        
        $mensaje = "¡Hola {$cita->patient->first_name}! 👋\n\n";
        $mensaje .= "Te escribimos de *{$clinica}* para recordarte tu próxima cita:\n\n";
        $mensaje .= "📅 *Fecha:* {$fecha}\n";
        $mensaje .= "⏰ *Hora:* {$hora}\n";
        $mensaje .= "🦷 *Tratamiento:* {$cita->treatment}\n\n";
        $mensaje .= "Por favor, confírmanos tu asistencia respondiendo a este mensaje con un 'SÍ' o avísanos si deseas reprogramar.\n\n";
        $mensaje .= "¡Te esperamos!";

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
                'Content-Type' => 'application/json'
            ])->post($url, [
                'number' => $telefono,
                'mediaMessage' => [
                    'mediatype' => 'document',
                    'caption' => $caption,
                    'media' => $base64,
                    'fileName' => $fileName
                ]
            ]);

            if ($response->successful()) {
                Log::info("Documento WhatsApp enviado a {$telefono}: {$fileName}");
                return true;
            }

            Log::error("Error Evolution API Documento: " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error("Excepción Evolution API Documento: " . $e->getMessage());
            return false;
        }
    }
}
