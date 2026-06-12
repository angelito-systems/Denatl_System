<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => 'required|exists:patients,id',
            'dentist_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'start_time' => 'required',
            'duration' => 'required|integer|min:15',
            'treatment' => 'required|string|max:255',
            'status' => 'required|string|in:pending,confirmed,cancelled,completed',
            'room' => 'nullable|string|max:255',
            'projector_status' => 'nullable|string',
            'notes' => 'nullable|string'
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $dentistId = $this->dentist_id;
            $date = \Carbon\Carbon::parse($this->date);
            $time = \Carbon\Carbon::parse($this->start_time);
            
            $dayOfWeek = $date->dayOfWeekIso;
            
            $schedule = \App\Models\DoctorSchedule::where('user_id', $dentistId)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_working', true)
                ->first();

            if (!$schedule) {
                $validator->errors()->add('start_time', 'El dentista no trabaja en este día.');
                return;
            }

            $start = \Carbon\Carbon::parse($schedule->start_time);
            $end = \Carbon\Carbon::parse($schedule->end_time);

            if ($time->lt($start) || $time->gt($end)) {
                $validator->errors()->add('start_time', "Fuera de horario laboral ({$schedule->start_time} - {$schedule->end_time}).");
            }

            if ($schedule->break_start && $schedule->break_end) {
                $breakStart = \Carbon\Carbon::parse($schedule->break_start);
                $breakEnd = \Carbon\Carbon::parse($schedule->break_end);
                
                if ($time->between($breakStart, $breakEnd)) {
                    $validator->errors()->add('start_time', "Horario de refrigerio ({$schedule->break_start} - {$schedule->break_end}).");
                }
            }
        });
    }
}
