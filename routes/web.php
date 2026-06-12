<?php

use App\Http\Controllers\Api\EvolutionWebhookController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EvolutionController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReniecController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TreatmentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('api/dentists/{dentist}/available-hours', [\App\Http\Controllers\Api\AvailableHoursController::class, 'getAvailableHours'])->name('api.dentists.available-hours');
    
    // Caja
    Route::get('cashbox', [\App\Http\Controllers\CashboxController::class, 'index'])->name('cashbox.index');
    Route::post('cashbox/open', [\App\Http\Controllers\CashboxController::class, 'open'])->name('cashbox.open');
    Route::post('cashbox/{cashbox}/close', [\App\Http\Controllers\CashboxController::class, 'close'])->name('cashbox.close');
    Route::post('cashbox/{cashbox}/transactions', [\App\Http\Controllers\CashboxController::class, 'storeTransaction'])->name('cashbox.transactions.store');

    Route::resource('patients', PatientController::class)->except(['create', 'edit']);
    Route::post('/pacientes/{patient}/evolutions', [EvolutionController::class, 'store'])->name('evolutions.store');
    Route::resource('treatment_contracts', App\Http\Controllers\TreatmentContractController::class)->only(['store', 'update', 'destroy', 'show']);
    Route::resource('documents', App\Http\Controllers\DocumentController::class);
    Route::post('/documents/{document}/sign', [App\Http\Controllers\DocumentController::class, 'sign']);
    Route::resource('appointments', AppointmentController::class);
    Route::post('appointments/{appointment}/remind', [AppointmentController::class, 'sendReminder'])->name('appointments.remind');
    Route::post('appointments/{appointment}/finish', [\App\Http\Controllers\ProjectorController::class, 'finishPatient'])->name('appointments.finish');
    Route::post('appointments/{appointment}/call', [\App\Http\Controllers\ProjectorController::class, 'callPatient'])->name('appointments.call');
    Route::post('appointments/{appointment}/start', [\App\Http\Controllers\ProjectorController::class, 'startPatient'])->name('appointments.start');
    
    Route::get('/projector', [\App\Http\Controllers\ProjectorController::class, 'index'])->name('projector.index');
    Route::get('/api/projector/state', [\App\Http\Controllers\ProjectorController::class, 'state'])->name('projector.state');
    Route::resource('treatments', TreatmentController::class);
    Route::resource('payments', PaymentController::class);
    // Administración y Marketing (Sólo Administrador)
    Route::middleware(['role:Administrador'])->group(function () {
        Route::get('reportes', [ReportController::class, 'index'])->name('reportes.index');
        Route::get('reportes/export', [ReportController::class, 'export'])->name('reportes.export');
        Route::resource('staff', UserController::class);
        Route::post('staff/{user}/schedule', [\App\Http\Controllers\DoctorScheduleController::class, 'update'])->name('staff.schedule.update');
        Route::get('mensajes', [MessageController::class, 'index'])->name('mensajes.index');
        Route::post('mensajes/send', [MessageController::class, 'send'])->name('mensajes.send');
        Route::post('mensajes/{conversation}/toggle-bot', [MessageController::class, 'toggleBot'])->name('mensajes.toggle-bot');
        Route::get('configuracion', [ConfigurationController::class, 'index'])->name('configuracion.index');
        Route::post('configuracion', [ConfigurationController::class, 'store'])->name('configuracion.store');
        Route::post('configuracion/logo', [ConfigurationController::class, 'uploadLogo'])->name('configuracion.logo');
        
        // Promotions and Ratings
        Route::resource('promotions', \App\Http\Controllers\PromotionController::class);
        Route::resource('ratings', \App\Http\Controllers\RatingController::class)->only(['index', 'destroy']);
        Route::resource('raffles', \App\Http\Controllers\RaffleController::class);
        Route::post('raffles/{raffle}/draw', [\App\Http\Controllers\RaffleController::class, 'draw'])->name('raffles.draw');
        Route::resource('roles', \App\Http\Controllers\RoleController::class)->only(['index', 'store', 'update']);
    });

    // Generación de PDFs
    Route::get('pacientes/{patient}/pdf/historia', [PatientController::class, 'downloadHistoria'])->name('pacientes.pdf.historia');
    Route::get('pacientes/{patient}/pdf/contrato', [PatientController::class, 'downloadContrato'])->name('pacientes.pdf.contrato');
    Route::post('pacientes/{patient}/pdf/odontograma', [PatientController::class, 'downloadOdontograma'])->name('pacientes.pdf.odontograma');
    Route::get('pagos/{payment}/pdf', [PaymentController::class, 'downloadComprobante'])->name('pagos.pdf');
    Route::get('pagos/{payment}/pdf-base64', [PaymentController::class, 'comprobanteBase64'])->name('pagos.pdf.base64');

    // SUNAT
    Route::post('/payments/{payment}/sunat/emitir', [PaymentController::class, 'emitirSunat'])->name('payments.sunat.emitir');
    Route::get('/payments/{payment}/sunat/download/{type}', [PaymentController::class, 'downloadSunat'])->name('payments.sunat.download');

    // Documentos y Contratos (Nuevo flujo con Firma)
    Route::post('pacientes/{patient}/documents/store', [DocumentController::class, 'store'])->name('pacientes.documents.store');
    Route::post('pacientes/{patient}/documents/upload', [DocumentController::class, 'upload'])->name('pacientes.documents.upload');
    Route::post('documents/{document}/sign', [DocumentController::class, 'sign'])->name('documents.sign');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    // Enviar documentos por WhatsApp
    Route::post('whatsapp/send-document', [MessageController::class, 'sendDocument'])->name('whatsapp.send_document');

    // API/External services
    // DNI
    Route::get('api/reniec/{dni}', [ReniecController::class, 'searchDni']);
    Route::get('api/reniec/search/{dni}', [ReniecController::class, 'search']); // Compatibilidad

    // RUC
    Route::get('api/sunat/ruc/{ruc}', [ReniecController::class, 'searchRuc']);

    // QZTray Signature
    Route::get('configuracion/qztray-cert', [ConfigurationController::class, 'getQzTrayCert'])->name('api.qztray.cert');
    Route::get('configuracion/sign-qztray', [ConfigurationController::class, 'signQzTray'])->name('api.qztray.sign');
});

// Evolution API Webhook (Sin Auth)
Route::post('/api/webhooks/evolution/{event?}', [EvolutionWebhookController::class, 'handle']);

require __DIR__.'/settings.php';
