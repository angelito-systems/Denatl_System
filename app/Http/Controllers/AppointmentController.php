<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Treatment;
use App\Models\User;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // For the calendar, we need to return JSON if requested, otherwise Inertia
        if ($request->wantsJson()) {
            $appointments = Appointment::with(['patient:id,first_name,last_name,dni', 'dentist:id,first_name,last_name'])
                ->when($request->start, function ($query, $start) {
                    return $query->where('date', '>=', $start);
                })
                ->when($request->end, function ($query, $end) {
                    return $query->where('date', '<=', $end);
                })
                ->get();

            return response()->json($appointments);
        }

        // Return Inertia page with lists for dropdowns
        $patients = Patient::select('id', 'first_name', 'last_name', 'dni')->get();
        // Assume dentists have role 'Dentista' or we just send all users for now
        $dentists = User::select('id', 'first_name', 'last_name')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->first_name.' '.$user->last_name,
            ];
        });

        $treatments = Treatment::select('id', 'name')->get();

        return Inertia::render('Appointments/Index', [
            'patients' => $patients,
            'dentists' => $dentists,
            'treatments' => $treatments,
            'appointmentsList' => Appointment::with(['patient', 'dentist'])->orderBy('date', 'desc')->paginate(15),
            'initialPatientId' => $request->query('patient_id'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAppointmentRequest $request)
    {
        $appointment = Appointment::create($request->validated());

        try {
            $whatsapp = app(WhatsAppService::class);
            $appointment->load(['patient', 'dentist']);
            $whatsapp->enviarRecordatorioCita($appointment);
        } catch (\Exception $e) {
            Log::error('Error enviando WhatsApp al crear cita: '.$e->getMessage());
        }

        return redirect()->back()->with('success', 'Cita guardada y notificación enviada por WhatsApp.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAppointmentRequest $request, Appointment $appointment, WhatsAppService $whatsapp)
    {
        $originalStatus = $appointment->status;
        $originalDate = $appointment->date ? Carbon::parse($appointment->date)->format('Y-m-d') : null;
        $originalTime = $appointment->start_time ? Carbon::parse($appointment->start_time)->format('H:i') : null;

        $appointment->update($request->validated());
        $appointment->load('patient');

        $newDate = $appointment->date ? Carbon::parse($appointment->date)->format('Y-m-d') : null;
        $newTime = $appointment->start_time ? Carbon::parse($appointment->start_time)->format('H:i') : null;

        // Notificaciones Automáticas por WhatsApp
        try {
            if ($originalStatus !== 'confirmed' && $appointment->status === 'confirmed') {
                $whatsapp->enviarConfirmacionCita($appointment);
            } elseif ($originalStatus !== 'cancelled' && $appointment->status === 'cancelled') {
                $whatsapp->enviarCancelacionCita($appointment);
            } elseif ($originalDate !== $newDate || $originalTime !== $newTime) {
                $whatsapp->enviarReprogramacionCita($appointment);
            }
        } catch (\Exception $e) {
            Log::error('Error en notificación automática de cita: '.$e->getMessage());
        }

        return redirect()->back()->with('success', 'Appointment updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        return redirect()->back()->with('success', 'Appointment deleted successfully.');
    }

    /**
     * Send a WhatsApp reminder to the patient.
     */
    public function sendReminder(Appointment $appointment, WhatsAppService $whatsapp)
    {
        try {
            $appointment->load(['patient', 'dentist']);
            $whatsapp->enviarRecordatorioCita($appointment);

            return redirect()->back()->with('success', 'Recordatorio enviado por WhatsApp exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error enviando recordatorio manual de cita: '.$e->getMessage());

            return redirect()->back()->with('error', 'No se pudo enviar el recordatorio. Verifica los logs.');
        }
    }
}
