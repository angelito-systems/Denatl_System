<?php

namespace App\Http\Controllers;

use App\Exports\AuditLogsExport;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class AuditLogController extends Controller
{
    /**
     * Display a listing of the audit logs.
     */
    public function index(Request $request)
    {
        $query = AuditLog::query();

        // Apply filters
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $search = "%{$request->search}%";
                $q->where('user_name', 'like', $search)
                    ->orWhere('user_email', 'like', $search)
                    ->orWhere('action', 'like', $search)
                    ->orWhere('module', 'like', $search)
                    ->orWhere('description', 'like', $search)
                    ->orWhere('correlation_id', 'like', $search);
            });
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('action_type')) {
            $query->where('action', 'like', "%{$request->action_type}%");
        }

        if ($request->filled('date_start') && $request->filled('date_end')) {
            $query->whereBetween('created_at', [
                $request->date_start.' 00:00:00',
                $request->date_end.' 23:59:59',
            ]);
        }

        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        if ($request->boolean('export')) {
            $format = $request->input('format', 'csv') === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
            $extension = $format === \Maatwebsite\Excel\Excel::CSV ? 'csv' : 'xlsx';

            AuditLogger::log(
                action: 'Export Audit Logs',
                module: 'AuditLog',
                description: "Exported audit logs to {$extension}"
            );

            return Excel::download(new AuditLogsExport($query), "audit_logs_{$sortDirection}.{$extension}", $format);
        }

        $logs = $query->paginate(20)->withQueryString();

        // Stats for Dashboard
        $stats = [
            'total' => AuditLog::count(),
            'info' => AuditLog::where('severity', 'info')->count(),
            'warning' => AuditLog::where('severity', 'warning')->count(),
            'critical' => AuditLog::where('severity', 'critical')->count(),
            'today' => AuditLog::whereDate('created_at', today())->count(),
        ];

        // Group chart data (last 7 days events count)
        $chartData = AuditLog::selectRaw('DATE(created_at) as date, count(*) as total')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date')
            ->toArray();

        return Inertia::render('AuditLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'severity', 'module', 'user_id', 'date_start', 'date_end', 'action_type', 'sort', 'direction']),
            'stats' => $stats,
            'chartData' => $chartData,
        ]);
    }

    /**
     * Display the specified audit log.
     */
    public function show(AuditLog $auditLog)
    {
        $relatedEvents = AuditLog::where('correlation_id', $auditLog->correlation_id)
            ->where('id', '!=', $auditLog->id)
            ->orderBy('created_at')
            ->get();

        return Inertia::render('AuditLogs/Show', [
            'auditLog' => $auditLog,
            'relatedEvents' => $relatedEvents,
        ]);
    }

    /**
     * Mark log as reviewed.
     */
    public function markAsReviewed(AuditLog $auditLog)
    {
        $auditLog->reviewed_at = now();
        $auditLog->reviewed_by = auth()->id();

        // Use DB directly or update quietly to not trigger its own auditable trait if we added it (though it's in ignored models)
        $auditLog->saveQuietly();

        return back()->with('success', 'Marcado como revisado.');
    }
}
