<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Document;
use Illuminate\Http\Request;
use App\Services\PdfGeneratorService;

class DocumentController extends Controller
{
    // Crear borrador (Generar Contrato)
    public function store(Request $request, Patient $patient)
    {
        $request->validate([
            'plantilla' => 'required|string|in:ortodoncia,implantes,consentimiento'
        ]);

        $nombres = [
            'ortodoncia' => 'Contrato de Ortodoncia',
            'implantes' => 'Contrato de Implantes',
            'consentimiento' => 'Consentimiento Informado'
        ];

        $patient->documents()->create([
            'type' => $request->plantilla,
            'name' => $nombres[$request->plantilla],
            'status' => 'Borrador'
        ]);

        return redirect()->back()->with('success', 'Borrador de documento generado.');
    }

    // Subir documento externo
    public function upload(Request $request, Patient $patient)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'name' => 'nullable|string|max:255'
        ]);

        $name = $request->input('name') ?: $request->file('file')->getClientOriginalName();

        $document = $patient->documents()->create([
            'type' => 'externo',
            'name' => $name,
            'status' => 'Subido',
            'signed_at' => now(),
        ]);

        $document->addMedia($request->file('file'))
            ->usingName($name)
            ->toMediaCollection('documents');

        return redirect()->back()->with('success', 'Documento subido exitosamente.');
    }

    // Firmar y generar PDF final
    public function sign(Request $request, Document $document, PdfGeneratorService $pdfService)
    {
        $request->validate([
            'signature' => 'required|string', // base64 image (Cliente)
            'admin_signature' => 'required|string' // base64 image (Admin)
        ]);

        // Guardar la firma en el registro
        $document->update([
            'signature' => $request->signature,
            'admin_signature' => $request->admin_signature,
            'signed_at' => now(),
            'status' => 'Firmado'
        ]);

        // Ahora generar el PDF físico inyectando las firmas
        $patient = $document->patient;
        
        $pdfBase64 = $pdfService->generarPlantilla($patient, $document->type, $document->signature, $document->admin_signature)->base64();
        
        $pdfContent = base64_decode($pdfBase64);
        $tempPath = sys_get_temp_dir() . '/' . uniqid() . '.pdf';
        file_put_contents($tempPath, $pdfContent);

        $document->addMedia($tempPath)
                ->usingName($document->name)
                ->usingFileName($document->type . '_' . $patient->id . '.pdf')
                ->toMediaCollection('documents');

        return redirect()->back()->with('success', 'Documento firmado y guardado exitosamente.');
    }

    public function destroy(Document $document)
    {
        $document->delete();
        return redirect()->back()->with('success', 'Documento eliminado.');
    }
}
