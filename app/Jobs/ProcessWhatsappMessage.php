<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\Patient;
use App\Models\WhatsappMessage;
use App\Services\EvolutionApiService;
use App\Services\WhatsappBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessWhatsappMessage implements ShouldQueue
{
    use Queueable;

    protected $messageData;

    public function __construct(array $messageData)
    {
        $this->messageData = $messageData;
    }

    public function handle(WhatsappBotService $botService): void
    {
        $message = $this->messageData;
        $key = $message['key'] ?? [];

        $isFromMe = $key['fromMe'] === true;

        $remoteJid = $key['remoteJid'] ?? '';
        if (! $remoteJid) {
            return;
        }

        // Obtener teléfono sin el @s.whatsapp.net
        $phoneNumber = explode('@', $remoteJid)[0];
        $messageId = $key['id'] ?? '';

        // Extraer texto o detectar tipo de media
        $text = '';
        $type = 'text';

        if (isset($message['message']['conversation'])) {
            $text = $message['message']['conversation'];
        } elseif (isset($message['message']['extendedTextMessage']['text'])) {
            $text = $message['message']['extendedTextMessage']['text'];
        } elseif (isset($message['message']['imageMessage'])) {
            $type = 'image';
            $caption = $message['message']['imageMessage']['caption'] ?? '';
            $text = trim("[Imagen] {$caption}");
        } elseif (isset($message['message']['videoMessage'])) {
            $type = 'video';
            $caption = $message['message']['videoMessage']['caption'] ?? '';
            $text = trim("[Video] {$caption}");
        } elseif (isset($message['message']['audioMessage'])) {
            $type = 'audio';
            $text = '[Audio]';
        } elseif (isset($message['message']['documentMessage'])) {
            $type = 'document';
            $caption = $message['message']['documentMessage']['title'] ?? $message['message']['documentMessage']['caption'] ?? '';
            $text = trim("[Documento] {$caption}");
        } elseif (isset($message['message']['stickerMessage'])) {
            $type = 'sticker';
            $text = '[Sticker]';
        }

        if ($text === '' && $type === 'text') {
            Log::info('Mensaje sin texto ignorado', ['id' => $messageId]);

            return;
        }

        // Lógica de descarga de multimedia
        $mediaUrl = null;
        if (in_array($type, ['image', 'video', 'audio', 'document', 'sticker'])) {
            try {
                $base64 = $message['base64'] ?? null;

                if (! $base64) {
                    $evolutionService = app(EvolutionApiService::class);
                    $base64 = $evolutionService->getBase64FromMediaMessage($message['message']);
                }

                if ($base64) {
                    $mediaData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64));
                    $mimeType = $message['message'][$type.'Message']['mimetype'] ?? 'application/octet-stream';
                    $extension = explode('/', explode(';', $mimeType)[0])[1] ?? 'bin';

                    if ($type === 'document' && isset($message['message']['documentMessage']['fileName'])) {
                        $extension = pathinfo($message['message']['documentMessage']['fileName'], PATHINFO_EXTENSION);
                    }

                    $filename = "whatsapp_media/{$messageId}.{$extension}";
                    Storage::disk('public')->put($filename, $mediaData);
                    $mediaUrl = "/storage/{$filename}";
                }
            } catch (\Exception $e) {
                Log::error('Error procesando multimedia de WA: '.$e->getMessage());
            }
        }

        // Buscar paciente existente
        $patient = Patient::where('phone', $phoneNumber)->first();

        // Buscar o crear conversación
        $conversation = Conversation::firstOrCreate(
            ['phone_number' => $phoneNumber],
            [
                'patient_id' => $patient ? $patient->id : null,
                'bot_status' => 'active',
                'bot_state' => 'welcome',
            ]
        );

        $conversation->update(['last_message_at' => now()]);

        // Guardar el mensaje en el historial
        WhatsappMessage::updateOrCreate(
            ['message_id' => $messageId],
            [
                'conversation_id' => $conversation->id,
                'remote_jid' => $remoteJid,
                'sender_type' => $isFromMe ? ($conversation->bot_status === 'active' ? 'bot' : 'advisor') : 'user',
                'content' => $text,
                'media_url' => $mediaUrl,
                'type' => $type,
                'read_status' => $isFromMe,
            ]
        );

        // Si el mensaje fue enviado por nosotros, no procesamos la respuesta automática
        if ($isFromMe) {
            return;
        }

        // Procesar con el bot
        $botService->processMessage($conversation, $text);
    }
}
