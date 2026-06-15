<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;

class PatientMediaController extends Controller
{
    public function store(Request $request, Patient $patient)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'name' => 'nullable|string|max:255',
        ]);

        $name = $request->input('name') ?: $request->file('file')->getClientOriginalName();

        $patient->addMedia($request->file('file'))
            ->usingName($name)
            ->toMediaCollection('documents');

        return redirect()->back()->with('success', 'Documento subido exitosamente.');
    }

    public function destroy(Patient $patient, $mediaId)
    {
        $media = $patient->media()->findOrFail($mediaId);
        $media->delete();

        return redirect()->back()->with('success', 'Documento eliminado.');
    }
}
