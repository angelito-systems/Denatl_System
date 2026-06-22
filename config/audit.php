<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Retention Period
    |--------------------------------------------------------------------------
    |
    | Define how many days audit logs should be kept in the database
    | before they are automatically pruned by the scheduled command.
    |
    */
    'retention_days' => env('AUDIT_RETENTION_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Ignored Models / Tables
    |--------------------------------------------------------------------------
    |
    | These models will not be audited automatically to avoid noise or 
    | saving temporary/cache data.
    |
    */
    'ignored_models' => [
        \App\Models\AuditLog::class,
        \Laravel\Sanctum\PersonalAccessToken::class,
        // Add other models if needed like Sessions, Jobs, etc.
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored Fields
    |--------------------------------------------------------------------------
    |
    | These fields will never be stored in the old_values / new_values 
    | properties of the audit log to ensure sensitive data is kept private.
    |
    */
    'ignored_fields' => [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'api_token',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
    ],

];
