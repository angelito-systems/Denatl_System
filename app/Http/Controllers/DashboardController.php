<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today()->toDateString();
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();

        $totalPatients = Patient::count();
        $appointmentsToday = Appointment::where('date', $today)->count();
        $revenueThisMonth = Payment::where('status', 'Pagado')
                                   ->where('created_at', '>=', $startOfMonth)
                                   ->sum('amount');
        
        $pendingPayments = Payment::where('status', 'Pendiente')->sum('amount');

        $nextAppointments = Appointment::with(['patient:id,first_name,last_name'])
                                       ->where('date', '>=', $today)
                                       ->orderBy('date')
                                       ->orderBy('start_time')
                                       ->take(5)
                                       ->get();

        $appointmentsByStatus = Appointment::selectRaw('status, count(*) as count')
                                           ->groupBy('status')
                                           ->get()
                                           ->pluck('count', 'status')
                                           ->toArray();

        return Inertia::render('Dashboard', [
            'kpis' => [
                'total_patients' => $totalPatients,
                'appointments_today' => $appointmentsToday,
                'revenue_this_month' => $revenueThisMonth,
                'pending_payments' => $pendingPayments,
            ],
            'next_appointments' => $nextAppointments,
            'appointments_by_status' => $appointmentsByStatus,
        ]);
    }
}
