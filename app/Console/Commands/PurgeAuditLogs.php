<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PurgeAuditLogs extends Command
{
    protected $signature = 'fs:audit:purge';

    protected $description = 'Purge audit log entries older than AUDIT_RETENTION_DAYS (0 = keep forever)';

    public function handle(): int
    {
        $retentionDays = config('audit.retention_days', 365);

        if ($retentionDays <= 0) {
            $this->info('Audit retention disabled (AUDIT_RETENTION_DAYS=0); no rows purged.');

            return self::SUCCESS;
        }

        $cutoff = Carbon::now()->subDays($retentionDays);

        $count = AuditLog::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Purged {$count} audit log(s) older than {$cutoff->toDateTimeString()}.");

        return self::SUCCESS;
    }
}
