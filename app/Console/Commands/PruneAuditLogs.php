<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PruneAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:prune {--days= : The number of days to retain logs (overrides config)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune old audit logs based on the retention policy';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days') ?? config('audit.retention_days', 365);
        
        $date = Carbon::now()->subDays($days);
        
        $this->info("Pruning audit logs older than {$days} days ({$date->format('Y-m-d')})...");

        // Delete in chunks to prevent memory/lock issues on millions of rows
        $deletedCount = 0;
        do {
            $count = AuditLog::where('created_at', '<', $date)
                ->limit(5000)
                ->delete();
                
            $deletedCount += $count;
        } while ($count > 0);

        $this->info("Deleted {$deletedCount} old audit logs.");
        
        return Command::SUCCESS;
    }
}
