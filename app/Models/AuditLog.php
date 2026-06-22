<?php

namespace App\Models;

use App\Enums\AuditSeverity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'audit_logs';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'error_details' => 'array',
        'severity' => AuditSeverity::class,
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the owning auditable model.
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
