<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Treatment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        // 1. Ingresos por mes (últimos 6 meses)
        $revenueByMonth = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $sum = Payment::where('status', 'Pagado')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('amount');
            $revenueByMonth[] = [
                'month' => $month->translatedFormat('M Y'),
                'revenue' => $sum
            ];
        }

        // 2. Tratamientos por categoría
        $treatmentsByCategory = Treatment::selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->get();

        // 3. Pagos por método
        $paymentsByMethod = Payment::selectRaw('payment_method, sum(amount) as total')
            ->where('status', 'Pagado')
            ->groupBy('payment_method')
            ->get();

        // 4. KPIS generales
        $totalPatients = Patient::count();
        $totalRevenue = Payment::where('status', 'Pagado')->sum('amount');
        $totalAppointments = Appointment::count();

        return Inertia::render('Reports/Index', [
            'revenueByMonth' => $revenueByMonth,
            'treatmentsByCategory' => $treatmentsByCategory,
            'paymentsByMethod' => $paymentsByMethod,
            'kpis' => [
                'totalPatients' => $totalPatients,
                'totalRevenue' => $totalRevenue,
                'totalAppointments' => $totalAppointments,
            ]
        ]);
    }

    public function export(Request $request)
    {
        // For simplicity, we just return a text response or a basic CSV.
        // In a real scenario we'd use Maatwebsite/Excel or DOMPDF.
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=reporte.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Paciente', 'Monto', 'Método', 'Fecha'];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Paciente', 'Monto', 'Método', 'Fecha']);
            
            $payments = Payment::with('patient')->get();
            foreach ($payments as $payment) {
                fputcsv($file, [
                    $payment->id,
                    $payment->patient ? $payment->patient->first_name . ' ' . $payment->patient->last_name : 'N/A',
                    $payment->amount,
                    $payment->payment_method,
                    $payment->created_at->format('Y-m-d')
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
