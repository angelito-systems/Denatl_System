<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientImageRequest;
use App\Http\Requests\UpdatePatientImageRequest;
use App\Models\ImageCategory;
use App\Models\Patient;
use App\Models\PatientImage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PatientImageController extends Controller
{
    public function index(Patient $patient)
    {
        Gate::authorize('patient-images.view');

        $images = $patient->patientImages()->with(['category', 'uploadedBy'])->get();
        $categories = ImageCategory::where('is_active', true)->get();

        return response()->json([
            'images' => $images,
            'categories' => $categories,
        ]);
    }

    public function store(StorePatientImageRequest $request, Patient $patient)
    {
        $data = $request->validated();
        $uploadedImages = [];

        if ($request->hasFile('files')) {
            $filesCount = count($request->file('files'));
            $index = 1;

            foreach ($request->file('files') as $file) {
                $fileName = time().'_'.$file->getClientOriginalName();
                $filePath = $file->storeAs('patient-images/'.$patient->id, $fileName, 'local');

                $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                if (! empty($data['title'])) {
                    $title = $filesCount > 1 ? $data['title'].' '.$index : $data['title'];
                }

                $uploadedImages[] = $patient->patientImages()->create([
                    'uploaded_by' => auth()->id(),
                    'category_id' => $data['category_id'],
                    'title' => $title,
                    'description' => $data['description'] ?? null,
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'taken_at' => isset($data['taken_at']) ? Carbon::parse($data['taken_at']) : now(),
                ]);

                $index++;
            }
        }

        return redirect()->back()->with('success', 'Imágenes subidas exitosamente.');
    }

    public function show(PatientImage $patientImage)
    {
        Gate::authorize('patient-images.view');

        if (! Storage::disk('local')->exists($patientImage->file_path)) {
            abort(404);
        }

        return Storage::disk('local')->response($patientImage->file_path);
    }

    public function download(PatientImage $patientImage)
    {
        Gate::authorize('patient-images.download');

        if (! Storage::disk('local')->exists($patientImage->file_path)) {
            abort(404);
        }

        return Storage::disk('local')->download($patientImage->file_path, $patientImage->title.'.'.pathinfo($patientImage->file_name, PATHINFO_EXTENSION));
    }

    public function update(UpdatePatientImageRequest $request, PatientImage $patientImage)
    {
        $patientImage->update($request->validated());

        return redirect()->back()->with('success', 'Imagen actualizada.');
    }

    public function destroy(PatientImage $patientImage)
    {
        Gate::authorize('patient-images.delete');

        $patientImage->delete();

        return redirect()->back()->with('success', 'Imagen eliminada.');
    }
}
