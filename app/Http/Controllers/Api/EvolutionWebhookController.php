<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsappMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EvolutionWebhookController extends Controller
{
    public function handle(Request $request, $eventName = null)
    {
        // Evolution API envía un objeto con event y data
        $payload = $request->all();

        // Guardar logs detallados en un archivo específico para revisar qué llega
        Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/evolution_webhook.log'),
        ])->info('NUEVO WEBHOOK RECIBIDO:', $payload);

        $event = $payload['event'] ?? $eventName ?? '';

        if ($event === 'messages.upsert' || $event === 'MESSAGES_UPSERT') {
            $data = $payload['data'] ?? [];
            // Verificar si hay mensajes en el payload (suele ser un array o objeto message)
            if (isset($data['message'])) {
                ProcessWhatsappMessage::dispatchSync($data);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
