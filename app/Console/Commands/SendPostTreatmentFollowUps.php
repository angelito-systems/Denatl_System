<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\EvolutionApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendPostTreatmentFollowUps extends Command
{
    protected $signature = 'app:send-post-treatment-follow-ups';

    protected $description = 'Envía mensajes de valoración y seguimiento post-tratamiento';

    public function handle(EvolutionApiService $evolutionApi)
    {
        // Enviar a citas completadas ayer
        $yesterday = Carbon::yesterday()->format('Y-m-d');

        $appointments = Appointment::with('patient')
            ->where('date', $yesterday)
            ->where('status', 'completed')
            ->get();

        $count = 0;
        foreach ($appointments as $appt) {
            if (! $appt->patient || ! $appt->patient->phone) {
                continue;
            }

            $patientName = $appt->patient->first_name;
            $msg = "🎉 *¡Hola {$patientName}!* \n\nEsperamos que te encuentres muy bien tras tu visita a la clínica ayer. 🦷✨\n\nNos encantaría saber cómo te fue. Por favor, califica nuestra atención del 1 al 5 respondiendo a este mensaje.\n\nTambién te recordamos que una limpieza dental cada 6 meses es ideal para mantener esa sonrisa impecable. ¡Te contactaremos pronto! ⭐";

            $evolutionApi->sendText($appt->patient->phone, $msg);
            $count++;
        }

        $this->info("Mensajes post-tratamiento enviados: {$count}");
    }
}
