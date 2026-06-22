<?php

namespace App\Services;

use App\Enums\AuditSeverity;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Log an action to the audit_logs table.
     */
    public static function log(
        string $action,
        string $module,
        AuditSeverity $severity = AuditSeverity::INFO,
        ?Model $model = null,
        array $oldValues = [],
        array $newValues = [],
        string $status = 'success',
        ?string $description = null,
        array $errorDetails = [],
        ?int $duration = null
    ): void {
        try {
            $request = Request::instance();
            $user = auth()->user();

            AuditLog::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'System/Guest',
                'user_email' => $user?->email,
                'user_role' => $user ? ($user->roles->first()?->name ?? 'User') : null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'os' => self::parseOS($request->userAgent()),
                'browser' => self::parseBrowser($request->userAgent()),
                'http_method' => $request->method(),
                'endpoint' => $request->fullUrl(),
                'module' => $module,
                'action' => $action,
                'auditable_type' => $model ? get_class($model) : null,
                'auditable_id' => $model ? $model->getKey() : null,
                'status' => $status,
                'status_code' => http_response_code() ?: 200,
                'duration' => $duration,
                'severity' => $severity,
                'description' => $description ?? "Performed {$action} on {$module}",
                'old_values' => empty($oldValues) ? null : $oldValues,
                'new_values' => empty($newValues) ? null : $newValues,
                'error_details' => empty($errorDetails) ? null : $errorDetails,
                'correlation_id' => self::getCorrelationId(),
            ]);
        } catch (\Exception $e) {
            // Failsafe: Do not let audit logging break the application
            report($e);
        }
    }

    /**
     * Get or generate a unique correlation ID for the current request lifecycle.
     */
    public static function getCorrelationId(): string
    {
        if (! app()->bound('audit_correlation_id')) {
            app()->instance('audit_correlation_id', (string) \Illuminate\Support\Str::uuid());
        }

        return app('audit_correlation_id');
    }

    /**
     * Parse OS from user agent.
     */
    private static function parseOS(?string $userAgent): ?string
    {
        if (! $userAgent) return null;

        $osPlatform = "Unknown OS Platform";
        $osArray = [
            '/windows nt 10/i'      =>  'Windows 10/11',
            '/windows nt 6.3/i'     =>  'Windows 8.1',
            '/windows nt 6.2/i'     =>  'Windows 8',
            '/windows nt 6.1/i'     =>  'Windows 7',
            '/macintosh|mac os x/i' =>  'Mac OS X',
            '/mac_powerpc/i'        =>  'Mac OS 9',
            '/linux/i'              =>  'Linux',
            '/ubuntu/i'             =>  'Ubuntu',
            '/iphone/i'             =>  'iPhone',
            '/ipod/i'               =>  'iPod',
            '/ipad/i'               =>  'iPad',
            '/android/i'            =>  'Android',
            '/blackberry/i'         =>  'BlackBerry',
            '/webos/i'              =>  'Mobile'
        ];

        foreach ($osArray as $regex => $value) {
            if (preg_match($regex, $userAgent)) {
                $osPlatform = $value;
                break;
            }
        }
        return $osPlatform;
    }

    /**
     * Parse Browser from user agent.
     */
    private static function parseBrowser(?string $userAgent): ?string
    {
        if (! $userAgent) return null;

        $browser = "Unknown Browser";
        $browserArray = [
            '/msie/i'      => 'Internet Explorer',
            '/firefox/i'   => 'Firefox',
            '/safari/i'    => 'Safari',
            '/chrome/i'    => 'Chrome',
            '/edge/i'      => 'Edge',
            '/opera/i'     => 'Opera',
            '/netscape/i'  => 'Netscape',
            '/maxthon/i'   => 'Maxthon',
            '/konqueror/i' => 'Konqueror',
            '/mobile/i'    => 'Handheld Browser'
        ];

        foreach ($browserArray as $regex => $value) {
            if (preg_match($regex, $userAgent)) {
                $browser = $value;
                break;
            }
        }
        return $browser;
    }
}
