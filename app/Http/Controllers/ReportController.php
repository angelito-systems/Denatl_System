<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Configuration;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\TreatmentContract;
use App\Services\PdfGeneratorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\LaravelPdf\Facades\Pdf;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'patient_search' => 'nullable|string',
        ], [
            'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ]);

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());
        $patientSearch = $request->input('patient_search');

        // Query Base Pagos
        $paymentsQuery = Payment::whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59']);
        // Query Base Citas
        $appointmentsQuery = Appointment::whereBetween('date', [$startDate, $endDate]);

        if ($patientSearch) {
            $patientsIds = Patient::where('first_name', 'like', "%{$patientSearch}%")
                ->orWhere('last_name', 'like', "%{$patientSearch}%")
                ->orWhere('dni', 'like', "%{$patientSearch}%")
                ->pluck('id');

            $paymentsQuery->whereIn('patient_id', $patientsIds);
            $appointmentsQuery->whereIn('patient_id', $patientsIds);
        } else {
            $patientsIds = collect();
        }

        // 1. Ingresos por mes (últimos 6 meses, ignorando rango para mostrar el gráfico histórico general, pero filtrado por cliente si hay)
        $revenueByMonth = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $q = Payment::where('status', 'Pagado')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month);

            if ($patientSearch) {
                $q->whereIn('patient_id', $patientsIds);
            }

            $sum = $q->sum('amount');
            $revenueByMonth[] = [
                'month' => $month->translatedFormat('M Y'),
                'revenue' => $sum,
            ];
        }

        // 2. Tratamientos por categoría (Usamos appointments en el rango en lugar del catálogo)
        $treatmentsData = (clone $appointmentsQuery)->selectRaw('treatment as category, count(*) as count')
            ->whereNotNull('treatment')
            ->groupBy('treatment')
            ->get();

        // 3. Pagos por método
        $paymentsByMethod = (clone $paymentsQuery)->selectRaw('payment_method, sum(amount) as total')
            ->where('status', 'Pagado')
            ->groupBy('payment_method')
            ->get();

        // 4. KPIS generales (Deudas y Créditos)
        $totalRevenue = (clone $paymentsQuery)->where('status', 'Pagado')->sum('amount');
        $totalDebts = (clone $paymentsQuery)->where('status', 'Pendiente')->sum('amount');

        $totalPatients = $patientSearch ? $patientsIds->count() : Patient::count();
        $totalAppointments = (clone $appointmentsQuery)->count();

        // 5. Productividad de Doctores
        $staffProductivity = (clone $appointmentsQuery)
            ->selectRaw('dentist_id, count(*) as count')
            ->whereNotNull('dentist_id')
            ->groupBy('dentist_id')
            ->orderByDesc('count')
            ->with('dentist:id,first_name,last_name')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->dentist ? $item->dentist->first_name.' '.$item->dentist->last_name : 'No Asignado',
                    'count' => $item->count,
                ];
            });

        // 6. Top Pacientes VIP (Facturación)
        $topPatients = (clone $paymentsQuery)
            ->selectRaw('patient_id, sum(amount) as total')
            ->where('status', 'Pagado')
            ->groupBy('patient_id')
            ->orderByDesc('total')
            ->with('patient:id,first_name,last_name')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->patient ? $item->patient->first_name.' '.$item->patient->last_name : 'Desconocido',
                    'total' => $item->total,
                ];
            });

        // 7. Estado de Contratos (Pagado vs Deuda)
        $contractPayments = (clone $paymentsQuery)->whereNotNull('treatment_contract_id')->where('status', 'Pagado')->sum('amount');
        $contractTotal = TreatmentContract::whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])->sum('total_cost');
        $contractsChart = [
            ['name' => 'Pagado', 'value' => $contractPayments],
            ['name' => 'Deuda', 'value' => max(0, $contractTotal - $contractPayments)],
        ];

        // 8. Nuevos pacientes por mes
        $newPatientsByMonth = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $count = Patient::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
            $newPatientsByMonth[] = [
                'month' => $month->translatedFormat('M Y'),
                'count' => $count,
            ];
        }

        return Inertia::render('Reports/Index', [
            'revenueByMonth' => $revenueByMonth,
            'treatmentsByCategory' => $treatmentsData,
            'paymentsByMethod' => $paymentsByMethod,
            'staffProductivity' => $staffProductivity,
            'topPatients' => $topPatients,
            'contractsChart' => $contractsChart,
            'newPatientsByMonth' => $newPatientsByMonth,
            'kpis' => [
                'totalPatients' => $totalPatients,
                'totalRevenue' => $totalRevenue,
                'totalDebts' => $totalDebts,
                'totalAppointments' => $totalAppointments,
            ],
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'patient_search' => $patientSearch,
            ],
        ]);
    }

    public function export(Request $request, PdfGeneratorService $pdfService)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());
        $patientSearch = $request->input('patient_search');

        $paymentsQuery = Payment::with('patient')->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59']);
        $appointmentsQuery = Appointment::with('patient')->whereBetween('date', [$startDate, $endDate]);

        $patientName = 'Todos los pacientes';

        if ($patientSearch) {
            $patientsIds = Patient::where('first_name', 'like', "%{$patientSearch}%")
                ->orWhere('last_name', 'like', "%{$patientSearch}%")
                ->orWhere('dni', 'like', "%{$patientSearch}%")
                ->pluck('id');

            $paymentsQuery->whereIn('patient_id', $patientsIds);
            $appointmentsQuery->whereIn('patient_id', $patientsIds);
            $patientName = $patientSearch;
        }

        $payments = $paymentsQuery->orderBy('created_at', 'desc')->get();
        $appointments = $appointmentsQuery->orderBy('date', 'desc')->get();

        $totalRevenue = $payments->where('status', 'Pagado')->sum('amount');
        $totalDebts = $payments->where('status', 'Pendiente')->sum('amount');

        $pdf = Pdf::view('pdfs.reporte', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'patientName' => $patientName,
            'totalRevenue' => $totalRevenue,
            'totalDebts' => $totalDebts,
            'payments' => $payments,
            'appointments' => $appointments,
            'fechaEmision' => now()->format('d/m/Y H:i'),
            'clinica' => [
                'nombre' => Configuration::get('clinica_nombre', 'Clínica Dental System'),
                'ruc' => Configuration::get('clinica_ruc', ''),
                'telefono' => Configuration::get('clinica_telefono', ''),
                'direccion' => Configuration::get('clinica_direccion', ''),
            ],
        ]);

        return $pdfService->applyBrowsershotConfig($pdf)
            ->format('a4')
            ->name('Reporte_Clinico_'.now()->format('Ymd_Hi').'.pdf')
            ->download();
    }
}
