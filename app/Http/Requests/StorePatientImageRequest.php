<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StorePatientImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('patient-images.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:image_categories,id',
            'files' => 'required|array',
            'files.*' => 'required|file|mimes:jpg,jpeg,png,webp,pdf,dicom,dcm|max:20480', // 20MB max per file
            'taken_at' => 'nullable|date',
            'description' => 'nullable|string',
            'title' => 'nullable|string|max:255',
        ];
    }
}
