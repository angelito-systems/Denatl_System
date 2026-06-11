<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use Inertia\Inertia;
use Illuminate\Http\Request;

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
        $dentists = User::select('id', 'first_name', 'last_name')->get()->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name
            ];
        });

        return Inertia::render('Appointments/Index', [
            'patients' => $patients,
            'dentists' => $dentists,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAppointmentRequest $request)
    {
        Appointment::create($request->validated());
        return redirect()->back()->with('success', 'Appointment created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $appointment->update($request->validated());
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
}
