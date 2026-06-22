<?php

namespace App\Listeners;

use App\Enums\AuditSeverity;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Events\Dispatcher;

class AuditAuthEvents
{
    /**
     * Handle user login events.
     */
    public function handleUserLogin(Login $event)
    {
        AuditLogger::log(
            action: 'Login',
            module: 'Authentication',
            severity: AuditSeverity::INFO,
            model: $event->user,
            description: "User logged in successfully"
        );
    }

    /**
     * Handle user logout events.
     */
    public function handleUserLogout(Logout $event)
    {
        AuditLogger::log(
            action: 'Logout',
            module: 'Authentication',
            severity: AuditSeverity::INFO,
            model: $event->user,
            description: "User logged out"
        );
    }

    /**
     * Handle user login failed events.
     */
    public function handleUserFailed(Failed $event)
    {
        AuditLogger::log(
            action: 'Failed Login',
            module: 'Authentication',
            severity: AuditSeverity::WARNING,
            status: 'failed',
            description: "Failed login attempt for user: " . ($event->credentials['email'] ?? 'Unknown'),
            newValues: ['email' => $event->credentials['email'] ?? null]
        );
    }

    /**
     * Handle user password reset events.
     */
    public function handleUserPasswordReset(PasswordReset $event)
    {
        AuditLogger::log(
            action: 'Password Reset',
            module: 'Authentication',
            severity: AuditSeverity::CRITICAL,
            model: $event->user,
            description: "User reset their password"
        );
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     * @return void
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(
            Login::class,
            [AuditAuthEvents::class, 'handleUserLogin']
        );

        $events->listen(
            Logout::class,
            [AuditAuthEvents::class, 'handleUserLogout']
        );

        $events->listen(
            Failed::class,
            [AuditAuthEvents::class, 'handleUserFailed']
        );

        $events->listen(
            PasswordReset::class,
            [AuditAuthEvents::class, 'handleUserPasswordReset']
        );
    }
}
