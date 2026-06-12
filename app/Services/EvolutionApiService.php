<?php

namespace App\Services;

use App\Models\Configuration;
use App\Models\Conversation;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionApiService
{
    protected $baseUrl;

    protected $instanceName;

    protected $globalApiKey;

    public function __construct()
    {
        // Limpiamos el trailing slash
        $this->baseUrl = rtrim(Configuration::get('whatsapp_api_url', env('EVOLUTION_API_URL')), '/');
        $this->instanceName = Configuration::get('whatsapp_instance', env('EVOLUTION_INSTANCE'));
        $this->globalApiKey = Configuration::get('whatsapp_api_key', env('EVOLUTION_GLOBAL_APIKEY'));
    }

    /**
     * Envía un mensaje de texto.
     */
    public function sendText(string $number, string $text)
    {
        if (! $this->baseUrl || ! $this->instanceName || ! $this->globalApiKey) {
            Log::error('Evolution API no configurada correctamente.');

            return false;
        }

        try {
            $url = "{$this->baseUrl}/message/sendText/{$this->instanceName}";
            $response = Http::withHeaders([
                'apikey' => $this->globalApiKey,
            ])->post($url, [
                'number' => $number,
                'text' => $text,
                'options' => [
                    'delay' => 1200,
                    'presence' => 'composing',
                    'linkPreview' => false,
                ],
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $messageId = $responseData['key']['id'] ?? 'OUT_'.uniqid();

                $conversation = Conversation::where('phone_number', $number)->first();
                if ($conversation) {
                    WhatsappMessage::create([
                        'conversation_id' => $conversation->id,
                        'remote_jid' => "{$number}@s.whatsapp.net",
                        'sender_type' => 'bot',
                        'content' => $text,
                        'type' => 'text',
                        'read_status' => true,
                        'message_id' => $messageId,
                    ]);
                    $conversation->update(['last_message_at' => now()]);
                }

                return true;
            } else {
                Log::error('Error de Evolution API: '.$response->body());

                return false;
            }
        } catch (\Exception $e) {
            Log::error('Error enviando mensaje por Evolution API: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Obtiene el base64 de un mensaje multimedia desde Evolution API.
     */
    public function getBase64FromMediaMessage(array $message)
    {
        if (! $this->baseUrl || ! $this->instanceName || ! $this->globalApiKey) {
            Log::error('Evolution API no configurada correctamente para obtener media.');

            return null;
        }

        try {
            $url = "{$this->baseUrl}/chat/getBase64FromMediaMessage/{$this->instanceName}";
            $response = Http::withHeaders([
                'apikey' => $this->globalApiKey,
            ])->post($url, [
                'message' => $message,
            ]);

            if ($response->successful()) {
                $responseData = $response->json();

                return $responseData['base64'] ?? null;
            } else {
                Log::error('Error de Evolution API al obtener media: '.$response->body());

                return null;
            }
        } catch (\Exception $e) {
            Log::error('Error obteniendo media por Evolution API: '.$e->getMessage());

            return null;
        }
    }
}
