<?php

namespace App\Enums;

enum AuditSeverity: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match($this) {
            self::INFO => 'Info',
            self::WARNING => 'Warning',
            self::CRITICAL => 'Critical',
        };
    }
}
