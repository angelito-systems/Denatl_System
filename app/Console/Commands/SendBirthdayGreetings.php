<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Services\EvolutionApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendBirthdayGreetings extends Command
{
    protected $signature = 'crm:send-birthday-greetings';

    protected $description = 'Send automatic birthday greetings via WhatsApp';

    public function handle(EvolutionApiService $evolutionApi)
    {
        $today = Carbon::today();

        $patients = Patient::whereMonth('date_of_birth', $today->month)
            ->whereDay('date_of_birth', $today->day)
            ->whereNotNull('phone')
            ->get();

        $count = 0;
        foreach ($patients as $patient) {
            $message = "🎂 ¡Feliz cumpleaños, {$patient->first_name}! Todo el equipo de la clínica dental te desea un excelente día y que lo pases rodeado de tus seres queridos. ¡Te esperamos pronto!";
            $evolutionApi->sendText($patient->phone, $message);
            $count++;
        }

        $this->info("Sent {$count} birthday greetings.");
    }
}
