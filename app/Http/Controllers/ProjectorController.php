<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Appointment;

class ProjectorController extends Controller
{
    public function index()
    {
        return Inertia::render('Projector/Index');
    }

    public function state()
    {
        $today = now()->format('Y-m-d');
        
        $calling = Appointment::with(['patient', 'dentist'])
            ->where('date', $today)
            ->where('projector_status', 'calling')
            ->orderBy('updated_at', 'desc')
            ->first();

        // Obtener pacientes actualmente en consultorio
        $inProgress = Appointment::with(['patient', 'dentist'])
            ->where('date', $today)
            ->where('projector_status', 'in_progress')
            ->orderBy('updated_at', 'desc')
            ->get();

        $waiting = Appointment::with(['patient', 'dentist'])
            ->where('date', $today)
            ->where(function($q) {
                $q->where('projector_status', 'waiting')
                  ->orWhere('status', 'confirmed');
            })
            ->whereNotIn('projector_status', ['calling', 'in_progress', 'finished'])
            ->orderBy('start_time', 'asc')
            ->take(5)
            ->get();

        return response()->json([
            'calling' => $calling,
            'inProgress' => $inProgress,
            'waiting' => $waiting
        ]);
    }

    public function callPatient(Appointment $appointment)
    {
        // Cancel all currently calling
        Appointment::where('date', now()->format('Y-m-d'))
            ->where('projector_status', 'calling')
            ->update(['projector_status' => 'in_progress']);

        $appointment->update([
            'projector_status' => 'calling',
            'status' => 'confirmed' // Or whatever fits
        ]);

        return redirect()->back()->with('success', 'Paciente llamado.');
    }

    public function startPatient(Appointment $appointment)
    {
        $appointment->update([
            'projector_status' => 'in_progress'
        ]);
        return redirect()->back()->with('success', 'Paciente en consulta.');
    }

    public function finishPatient(Appointment $appointment)
    {
        $appointment->update([
            'projector_status' => 'finished',
            'status' => 'completed'
        ]);

        // Auto-call al siguiente paciente en espera para este mismo doctor
        $nextAppointment = Appointment::where('date', now()->format('Y-m-d'))
            ->where('dentist_id', $appointment->dentist_id)
            ->where(function($q) {
                $q->where('projector_status', 'waiting')
                  ->orWhere('status', 'confirmed');
            })
            ->whereNotIn('projector_status', ['calling', 'in_progress', 'finished'])
            ->orderBy('start_time', 'asc')
            ->first();

        if ($nextAppointment) {
            $nextAppointment->update([
                'projector_status' => 'calling',
                'status' => 'confirmed'
            ]);
            return redirect()->back()->with('success', 'Consulta finalizada y próximo paciente llamado automáticamente.');
        }

        return redirect()->back()->with('success', 'Consulta finalizada.');
    }
}
