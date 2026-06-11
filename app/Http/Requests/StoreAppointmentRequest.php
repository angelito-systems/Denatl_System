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
            'notes' => 'nullable|string'
        ];
    }
}
