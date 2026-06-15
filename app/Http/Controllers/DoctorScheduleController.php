<?php

namespace App\Http\Controllers;

use App\Models\DoctorSchedule;
use App\Models\User;
use Illuminate\Http\Request;

class DoctorScheduleController extends Controller
{
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'schedules' => 'required|array|size:7',
            'schedules.*.day_of_week' => 'required|integer|min:1|max:7',
            'schedules.*.start_time' => 'nullable|date_format:H:i',
            'schedules.*.end_time' => 'nullable|date_format:H:i',
            'schedules.*.break_start' => 'nullable|date_format:H:i',
            'schedules.*.break_end' => 'nullable|date_format:H:i',
            'schedules.*.is_working' => 'required|boolean',
        ]);

        foreach ($validated['schedules'] as $scheduleData) {
            DoctorSchedule::updateOrCreate(
                ['user_id' => $user->id, 'day_of_week' => $scheduleData['day_of_week']],
                [
                    'start_time' => $scheduleData['start_time'],
                    'end_time' => $scheduleData['end_time'],
                    'break_start' => $scheduleData['break_start'],
                    'break_end' => $scheduleData['break_end'],
                    'is_working' => $scheduleData['is_working'],
                ]
            );
        }

        return redirect()->back()->with('success', 'Horario actualizado correctamente.');
    }
}
