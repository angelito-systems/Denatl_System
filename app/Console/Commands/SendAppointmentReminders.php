<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Configuration;
use App\Models\WhatsappMessage;
use App\Services\EvolutionApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    protected $signature = 'app:send-appointment-reminders';

    protected $description = 'Envía recordatorios de citas según la configuración global';

    public function handle(EvolutionApiService $evolutionApi)
    {
        $this->info('Verificando citas para recordatorios...');

        $now = Carbon::now();

        // Obtener configuración
        $r1_val = (int) Configuration::get('reminder_1_value', '24');
        $r1_unit = Configuration::get('reminder_1_unit', 'hours');

        $r2_val = (int) Configuration::get('reminder_2_value', '2');
        $r2_unit = Configuration::get('reminder_2_unit', 'hours');

        // Calcular tiempos objetivo
        $target1 = $r1_unit === 'minutes' ? clone $now->addMinutes($r1_val) : clone $now->addHours($r1_val);
        $target2 = $r2_unit === 'minutes' ? clone $now->addMinutes($r2_val) : clone $now->addHours($r2_val);

        // Buscar citas que su start_time coincida
        // El formato de la cita es date (Y-m-d) y start_time (H:i:s)
        $appointments = Appointment::with('patient')->where('status', '!=', 'cancelled')->get();

        $count = 0;
        foreach ($appointments as $appt) {
            if (! $appt->patient || ! $appt->patient->phone) {
                continue;
            }

            $apptTime = Carbon::parse($appt->date.' '.$appt->start_time);

            // Verificar si cae en la ventana de recordatorio 1 (con 1 minuto de margen)
            $diff1 = abs($apptTime->diffInMinutes($target1, false));
            $diff2 = abs($apptTime->diffInMinutes($target2, false));

            if ($diff1 === 0 && ! $this->alreadySent($appt, 1)) {
                $this->sendReminder($evolutionApi, $appt, 1);
                $count++;
            } elseif ($diff2 === 0 && ! $this->alreadySent($appt, 2)) {
                $this->sendReminder($evolutionApi, $appt, 2);
                $count++;
            }
        }

        $this->info("Recordatorios enviados: {$count}");
    }

    private function alreadySent($appointment, $level)
    {
        return WhatsappMessage::where('patient_id', $appointment->patient_id)
            ->where('message', 'like', "%Recordatorio%{$appointment->date}%")
            ->where('created_at', '>=', now()->subHours(1))
            ->exists();
    }

    private function sendReminder($evolutionApi, $appointment, $level)
    {
        $patientName = $appointment->patient->first_name;
        $dateStr = Carbon::parse($appointment->date)->format('d/m/Y');
        $timeStr = $appointment->start_time;

        $msg = "⏰ *Recordatorio de Cita*\n\nHola {$patientName}, te recordamos que tienes una cita programada:\n📅 Fecha: {$dateStr}\n🕐 Hora: {$timeStr}\n\nPor favor, responde confirmando tu asistencia. ¡Te esperamos!";

        $evolutionApi->sendText($appointment->patient->phone, $msg);
    }
}
