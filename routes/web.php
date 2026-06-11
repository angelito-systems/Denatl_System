<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('patients', App\Http\Controllers\PatientController::class);
    Route::resource('appointments', App\Http\Controllers\AppointmentController::class);
    Route::resource('treatments', App\Http\Controllers\TreatmentController::class);
    Route::resource('payments', App\Http\Controllers\PaymentController::class);
    Route::get('reportes', [App\Http\Controllers\ReportController::class, 'index'])->name('reportes.index');
    Route::get('reportes/export', [App\Http\Controllers\ReportController::class, 'export'])->name('reportes.export');
    Route::resource('staff', App\Http\Controllers\UserController::class);
    Route::get('mensajes', [App\Http\Controllers\MessageController::class, 'index'])->name('mensajes.index');
    Route::post('mensajes/send', [App\Http\Controllers\MessageController::class, 'send'])->name('mensajes.send');
    Route::get('configuracion', [App\Http\Controllers\ConfigurationController::class, 'index'])->name('configuracion.index');
    Route::post('configuracion', [App\Http\Controllers\ConfigurationController::class, 'store'])->name('configuracion.store');
    
    // Generación de PDFs
    Route::get('pacientes/{patient}/pdf/historia', [App\Http\Controllers\PatientController::class, 'downloadHistoria'])->name('pacientes.pdf.historia');
    Route::get('pacientes/{patient}/pdf/contrato', [App\Http\Controllers\PatientController::class, 'downloadContrato'])->name('pacientes.pdf.contrato');
    Route::get('pagos/{payment}/pdf', [App\Http\Controllers\PaymentController::class, 'downloadComprobante'])->name('pagos.pdf');

    // Documentos y Contratos (Nuevo flujo con Firma)
    Route::post('pacientes/{patient}/documents/store', [App\Http\Controllers\DocumentController::class, 'store'])->name('pacientes.documents.store');
    Route::post('pacientes/{patient}/documents/upload', [App\Http\Controllers\DocumentController::class, 'upload'])->name('pacientes.documents.upload');
    Route::post('documents/{document}/sign', [App\Http\Controllers\DocumentController::class, 'sign'])->name('documents.sign');
    Route::delete('documents/{document}', [App\Http\Controllers\DocumentController::class, 'destroy'])->name('documents.destroy');
    
    // Enviar documentos por WhatsApp
    Route::post('whatsapp/send-document', [App\Http\Controllers\MessageController::class, 'sendDocument'])->name('whatsapp.send_document');

    // API/External services
    Route::get('api/reniec/{dni}', [App\Http\Controllers\ReniecController::class, 'search'])->name('api.reniec');
});

require __DIR__.'/settings.php';
