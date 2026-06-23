<?php

namespace App\Http\Middleware;

use App\Enums\AuditSeverity;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Define a start time to measure duration
        $request->attributes->set('audit_start_time', microtime(true));

        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     */
    public function terminate(Request $request, Response $response): void
    {
        // Ignore certain paths
        $ignoredPaths = ['build/*', 'assets/*', 'images/*', 'favicon.ico'];
        foreach ($ignoredPaths as $path) {
            if ($request->is($path)) {
                return;
            }
        }

        $duration = 0;
        if ($startTime = $request->attributes->get('audit_start_time')) {
            $duration = (int) ((microtime(true) - $startTime) * 1000);
        }

        $statusCode = $response->getStatusCode();
        
        $severity = AuditSeverity::INFO;
        if ($statusCode >= 400 && $statusCode < 500) {
            $severity = AuditSeverity::WARNING;
        } elseif ($statusCode >= 500) {
            $severity = AuditSeverity::CRITICAL;
        }

        // We avoid logging purely static GET requests that are successful to save space,
        // EXCEPT if the user is in the /admin/audits panel (so we log their searches/exports)
        // OR if it's an error.
        $isAuditPanel = $request->is('audits*') || $request->is('admin/audits*');
        $isWriteMethod = in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE']);
        $isError = $statusCode >= 400;

        if (! $isWriteMethod && ! $isError && ! $isAuditPanel) {
            return;
        }

        $action = $request->method() . ' Request';
        $module = 'System';
        
        if ($isAuditPanel && $request->isMethod('GET')) {
            $action = 'Viewed Audit Logs';
            $module = 'AuditLog';
        }

        $errorDetails = [];
        if ($exception = $response->exception) {
            // Only Administrador will see the full stack trace in the UI, but we log it here
            $errorDetails = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        AuditLogger::log(
            action: $action,
            module: $module,
            severity: $severity,
            status: $statusCode < 400 ? 'success' : 'failed',
            description: "HTTP {$request->method()} {$request->path()} [{$statusCode}]",
            errorDetails: $errorDetails,
            duration: $duration
        );
    }
}
