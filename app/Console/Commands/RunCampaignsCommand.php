<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\Patient;
use App\Services\WhatsappBotService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunCampaignsCommand extends Command
{
    protected $signature = 'campaigns:run';

    protected $description = 'Ejecuta las campañas automáticas programadas';

    public function handle(WhatsappBotService $botService)
    {
        $now = Carbon::now();

        $campaigns = Campaign::where('status', 'Encendida')
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', $now->toDateString());
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $now->toDateString());
            })
            ->get();

        foreach ($campaigns as $campaign) {
            // Verificar horario (si aplica)
            if ($campaign->send_time) {
                $sendTime = Carbon::createFromFormat('H:i:s', $campaign->send_time);
                // Ejecutar si la hora actual coincide (con un margen de 5 min asumiendo cron cada 5)
                if ($now->diffInMinutes($sendTime) > 5) {
                    continue;
                }
            }

            // Ejecución básica: Obtener pacientes con número
            $patients = Patient::whereNotNull('phone')->get();
            $count = 0;

            foreach ($patients as $patient) {
                try {
                    $message = str_replace(
                        ['{nombre}', '{apellido}'],
                        [$patient->first_name, $patient->last_name],
                        $campaign->message
                    );

                    $botService->sendMessage($patient->phone, $message);
                    $count++;
                    sleep(1); // Evitar rate limits
                } catch (\Exception $e) {
                    Log::error("Error enviando campaña {$campaign->id} al paciente {$patient->id}: ".$e->getMessage());
                }
            }

            $this->info("Campaña '{$campaign->name}' enviada a {$count} pacientes.");
            Log::info("Campaña '{$campaign->name}' ejecutada para {$count} pacientes.");
        }
    }
}
