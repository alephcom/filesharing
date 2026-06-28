<?php

namespace App\Console\Commands;

use App\Services\AuditExporter;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ExportAuditLogs extends Command
{
    protected $signature = 'fs:audit:export
                            {--from= : Start date (Y-m-d)}
                            {--to= : End date (Y-m-d)}
                            {--format= : Export format: csv or json}
                            {--output= : Output path relative to storage/app}';

    protected $description = 'Export audit logs for a date range';

    public function handle(AuditExporter $exporter): int
    {
        $format = $this->option('format') ?: config('audit.export_default_format', 'csv');

        if (! in_array($format, ['csv', 'json'], true)) {
            $this->error('Format must be csv or json.');

            return self::FAILURE;
        }

        $from = $this->option('from') ? Carbon::parse($this->option('from'))->startOfDay() : null;
        $to = $this->option('to') ? Carbon::parse($this->option('to'))->endOfDay() : null;

        $output = $this->option('output') ?: 'audit/audit-export-'.now()->format('Y-m-d-His').'.'.$format;

        $count = $exporter->exportToPath($output, $format, $from, $to);
        $fullPath = Storage::disk('local')->path($output);

        $this->info("Exported {$count} audit log(s) to {$fullPath}.");

        return self::SUCCESS;
    }
}
