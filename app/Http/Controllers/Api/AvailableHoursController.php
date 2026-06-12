<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DoctorSchedule;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AvailableHoursController extends Controller
{
    public function getAvailableHours(Request $request, User $dentist)
    {
        $request->validate([
            'date' => 'required|date'
        ]);

        $requestedDate = Carbon::parse($request->date)->startOfDay();
        $today = Carbon::now()->startOfDay();

        // Si piden una fecha pasada, empezamos a buscar desde hoy
        if ($requestedDate->lt($today)) {
            $requestedDate = $today->copy();
        }

        $maxDaysToSearch = 30; // Evitar loop infinito
        $currentDate = $requestedDate->copy();
        $availableSlots = [];
        $foundDate = null;

        for ($i = 0; $i < $maxDaysToSearch; $i++) {
            $dayOfWeek = $currentDate->dayOfWeek;
            // Carbon dayOfWeek: 0 = Sunday, 1 = Monday, etc.
            // Nuestra app asume que 1=Lunes, 7=Domingo? Hay que verificar cómo guardan day_of_week en DoctorSchedule.
            // Si guardan 1=Lunes ... 7=Domingo (isoFormat):
            $isoDayOfWeek = $currentDate->isoWeekday();

            $schedule = DoctorSchedule::where('user_id', $dentist->id)
                ->where('day_of_week', $isoDayOfWeek)
                ->where('is_working', true)
                ->first();

            if ($schedule && $schedule->start_time && $schedule->end_time) {
                $slots = $this->calculateSlotsForDay($dentist->id, $currentDate, $schedule);
                
                if (count($slots) > 0) {
                    $availableSlots = $slots;
                    $foundDate = $currentDate->format('Y-m-d');
                    break;
                }
            }

            // Mover al día siguiente
            $currentDate->addDay();
        }

        return response()->json([
            'requested_date' => $request->date,
            'available_date' => $foundDate,
            'available_slots' => $availableSlots,
            'requires_date_change' => $foundDate !== $request->date
        ]);
    }

    private function calculateSlotsForDay($dentistId, Carbon $date, DoctorSchedule $schedule)
    {
        $slots = [];
        $startTime = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->start_time);
        $endTime = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->end_time);
        
        $breakStart = $schedule->break_start ? Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->break_start) : null;
        $breakEnd = $schedule->break_end ? Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->break_end) : null;

        $appointments = Appointment::where('dentist_id', $dentistId)
            ->where('date', $date->format('Y-m-d'))
            ->whereNotIn('status', ['cancelled'])
            ->get();

        $currentTime = $startTime->copy();
        $now = Carbon::now();

        while ($currentTime->lt($endTime)) {
            $slotEnd = $currentTime->copy()->addMinutes(30);

            // Verificar si el bloque ya pasó (si es hoy)
            if ($date->isToday() && $currentTime->lt($now)) {
                $currentTime->addMinutes(30);
                continue;
            }

            // Verificar si choca con refrigerio
            $inBreak = false;
            if ($breakStart && $breakEnd) {
                if ($currentTime->gte($breakStart) && $currentTime->lt($breakEnd)) {
                    $inBreak = true;
                }
            }

            // Verificar si choca con otra cita
            $isBooked = false;
            foreach ($appointments as $apt) {
                $aptStart = Carbon::parse($date->format('Y-m-d') . ' ' . $apt->start_time);
                // Asumimos 30 min de duración para simplificar
                $aptEnd = $aptStart->copy()->addMinutes(30); 

                // Si el slot se cruza con una cita existente
                if ($currentTime->gte($aptStart) && $currentTime->lt($aptEnd)) {
                    $isBooked = true;
                    break;
                }
            }

            if (!$inBreak && !$isBooked) {
                $slots[] = $currentTime->format('H:i');
            }

            $currentTime->addMinutes(30);
        }

        return $slots;
    }
}
