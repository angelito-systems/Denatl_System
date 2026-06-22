<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AuditLogsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(protected Builder $query)
    {
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Date',
            'User ID',
            'User Name',
            'Role',
            'IP Address',
            'Module',
            'Action',
            'Status',
            'Status Code',
            'Severity',
            'Duration (ms)',
            'Description',
            'Correlation ID',
            'Reviewed At',
        ];
    }

    public function map($log): array
    {
        return [
            $log->id,
            $log->created_at->toIso8601String(),
            $log->user_id,
            $log->user_name,
            $log->user_role,
            $log->ip_address,
            $log->module,
            $log->action,
            $log->status,
            $log->status_code,
            $log->severity->value,
            $log->duration,
            $log->description,
            $log->correlation_id,
            $log->reviewed_at ? $log->reviewed_at->toIso8601String() : '',
        ];
    }
}
