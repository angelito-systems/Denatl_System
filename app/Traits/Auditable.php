<?php

namespace App\Traits;

use App\Enums\AuditSeverity;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

trait Auditable
{
    /**
     * Boot the trait.
     */
    public static function bootAuditable()
    {
        if (in_array(static::class, config('audit.ignored_models', []))) {
            return;
        }

        static::created(function (Model $model) {
            self::logAuditAction($model, 'created');
        });

        static::updated(function (Model $model) {
            self::logAuditAction($model, 'updated');
        });

        static::deleted(function (Model $model) {
            self::logAuditAction($model, 'deleted');
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function (Model $model) {
                self::logAuditAction($model, 'restored');
            });
        }
    }

    /**
     * Log the audit action.
     */
    protected static function logAuditAction(Model $model, string $action)
    {
        $oldValues = [];
        $newValues = [];

        if ($action === 'updated') {
            $oldValues = Arr::except($model->getOriginal(), static::getAuditIgnoredFields());
            $newValues = Arr::except($model->getAttributes(), static::getAuditIgnoredFields());
            
            // Only keep what actually changed
            $changedKeys = array_keys($model->getChanges());
            $oldValues = Arr::only($oldValues, $changedKeys);
            $newValues = Arr::only($newValues, $changedKeys);

            if (empty($oldValues) && empty($newValues)) {
                return; // Nothing meaningful changed
            }
        } elseif ($action === 'created') {
            $newValues = Arr::except($model->getAttributes(), static::getAuditIgnoredFields());
        } elseif ($action === 'deleted') {
            $oldValues = Arr::except($model->getOriginal(), static::getAuditIgnoredFields());
        }

        $className = class_basename(static::class);

        AuditLogger::log(
            action: ucfirst($action),
            module: $className,
            severity: $action === 'deleted' ? AuditSeverity::WARNING : AuditSeverity::INFO,
            model: $model,
            oldValues: $oldValues,
            newValues: $newValues,
            description: "{$className} {$action} ID {$model->getKey()}"
        );
    }

    /**
     * Get fields that should not be logged.
     */
    protected static function getAuditIgnoredFields(): array
    {
        return array_merge(
            config('audit.ignored_fields', []),
            ['created_at', 'updated_at', 'deleted_at']
        );
    }
}
