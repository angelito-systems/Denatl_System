<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // KPIs
        $totalPatients = Patient::count();
        $newPatients = Patient::whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])->count();

        $appointmentsToday = Appointment::where('date', Carbon::today()->toDateString())->count();

        $revenuePeriod = Payment::where('status', 'Pagado')
            ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->sum('amount');

        $pendingPayments = Payment::where('status', 'Pendiente')
            ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->sum('amount');

        // Próximas citas de hoy que no han finalizado (con margen de 1 hora hacia atrás para no mostrar citas muy antiguas)
        $nextAppointments = Appointment::with(['patient:id,first_name,last_name'])
            ->where('date', Carbon::today()->toDateString())
            ->where(function ($q) {
                $q->whereNull('projector_status')
                  ->orWhere('projector_status', '!=', 'finished');
            })
            ->whereTime('start_time', '>=', Carbon::now()->subHours(1)->format('H:i:s'))
            ->orderBy('start_time')
            ->take(10)
            ->get();

        // Top Tratamientos en el periodo
        $topTreatments = Appointment::selectRaw('treatment as name, count(*) as count')
            ->whereNotNull('treatment')
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('treatment')
            ->orderByDesc('count')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => rand(1000, 9999),
                    'name' => $item->name,
                    'count' => $item->count,
                ];
            });

        // Próximos Cumpleaños (próximos 30 días)
        $today = Carbon::today();
        $thirtyDaysFromNow = Carbon::today()->addDays(30);

        $upcomingBirthdays = Patient::whereNotNull('date_of_birth')
            ->get()
            ->filter(function ($patient) use ($today, $thirtyDaysFromNow) {
                try {
                    $bdayThisYear = Carbon::parse($patient->date_of_birth)->year($today->year);
                    if ($bdayThisYear->isPast() && ! $bdayThisYear->isToday()) {
                        $bdayThisYear->addYear();
                    }

                    return $bdayThisYear->between($today, $thirtyDaysFromNow);
                } catch (\Exception $e) {
                    return false;
                }
            })
            ->sortBy(function ($patient) use ($today) {
                $bdayThisYear = Carbon::parse($patient->date_of_birth)->year($today->year);
                if ($bdayThisYear->isPast() && ! $bdayThisYear->isToday()) {
                    $bdayThisYear->addYear();
                }

                return $bdayThisYear->timestamp;
            })
            ->take(5)
            ->map(function ($patient) use ($today) {
                $bdayThisYear = Carbon::parse($patient->date_of_birth)->year($today->year);
                if ($bdayThisYear->isPast() && ! $bdayThisYear->isToday()) {
                    $bdayThisYear->addYear();
                }

                $diffDays = $today->diffInDays($bdayThisYear, false);
                $dateText = $diffDays == 0 ? 'Hoy' : ($diffDays == 1 ? 'Mañana' : "En $diffDays días");

                return [
                    'id' => $patient->id,
                    'name' => $patient->first_name.' '.$patient->last_name,
                    'age' => Carbon::parse($patient->date_of_birth)->age + ($bdayThisYear->year > $today->year ? 1 : 0),
                    'date' => $dateText,
                ];
            })->values();

        // Actividad reciente (últimas 5 citas completadas o pagos)
        Carbon::setLocale('es');
        $recentPayments = Payment::with('patient')->orderBy('created_at', 'desc')->take(3)->get()->map(function ($p) {
            return [
                'id' => 'p_'.$p->id,
                'title' => 'Pago registrado',
                'description' => ($p->patient ? $p->patient->first_name.' '.$p->patient->last_name : 'Paciente').' - S/ '.number_format($p->amount, 2),
                'time' => $p->created_at->diffForHumans(),
                'created_at' => $p->created_at,
                'type' => 'payment',
            ];
        });

        $recentAppointments = Appointment::with('patient')->where('status', 'completed')->orderBy('updated_at', 'desc')->take(3)->get()->map(function ($a) {
            return [
                'id' => 'a_'.$a->id,
                'title' => 'Cita completada',
                'description' => ($a->patient ? $a->patient->first_name.' '.$a->patient->last_name : 'Paciente').' - '.$a->treatment,
                'time' => $a->updated_at->diffForHumans(),
                'created_at' => $a->updated_at,
                'type' => 'appointment',
            ];
        });

        $recentActivities = $recentPayments->concat($recentAppointments)->sortByDesc('created_at')->take(5)->values();

        // Gráfico de Ventas (Línea)
        $salesChart = Payment::selectRaw('DATE(created_at) as date, sum(amount) as total')
            ->where('status', 'Pagado')
            ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Gráfico de Zonas (Dona)
        $zonesChart = Patient::selectRaw('LOWER(TRIM(address)) as address_name, count(*) as count')
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->groupBy('address_name')
            ->orderByDesc('count')
            ->take(7)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => ucwords($item->address_name),
                    'count' => $item->count,
                ];
            });

        return Inertia::render('Dashboard', [
            'kpis' => [
                'total_patients' => $totalPatients,
                'new_patients' => $newPatients,
                'appointments_today' => $appointmentsToday,
                'revenue_period' => $revenuePeriod,
                'pending_payments' => $pendingPayments,
            ],
            'next_appointments' => $nextAppointments,
            'top_treatments' => $topTreatments,
            'upcoming_birthdays' => $upcomingBirthdays,
            'recent_activities' => $recentActivities,
            'sales_chart' => $salesChart,
            'zones_chart' => $zonesChart,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
}
