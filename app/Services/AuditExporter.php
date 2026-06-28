<?php

namespace App\Services;

use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditExporter
{
    private const CHUNK_SIZE = 500;

    /**
     * @return Builder<AuditLog>
     */
    public function query(?Carbon $from = null, ?Carbon $to = null): Builder
    {
        return AuditLog::query()
            ->with(['bundle', 'actor'])
            ->when($from, fn (Builder $q) => $q->where('created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->where('created_at', '<=', $to->copy()->endOfDay()))
            ->orderBy('created_at');
    }

    public function exportToPath(string $path, string $format, ?Carbon $from = null, ?Carbon $to = null): int
    {
        $directory = dirname($path);
        if ($directory !== '.' && ! Storage::disk('local')->exists($directory)) {
            Storage::disk('local')->makeDirectory($directory);
        }

        $handle = fopen(Storage::disk('local')->path($path), 'wb');

        try {
            $query = $this->query($from, $to);

            return $format === 'json'
                ? $this->writeJson($query, $handle)
                : $this->writeCsv($query, $handle);
        } finally {
            fclose($handle);
        }
    }

    public function downloadResponse(string $format, ?Carbon $from = null, ?Carbon $to = null): StreamedResponse
    {
        $filename = 'audit-export-'.now()->format('Y-m-d-His').'.'.$format;

        return response()->streamDownload(function () use ($format, $from, $to) {
            $output = fopen('php://output', 'wb');
            $query = $this->query($from, $to);

            if ($format === 'json') {
                $this->writeJson($query, $output);
            } else {
                $this->writeCsv($query, $output);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => $format === 'json' ? 'application/json' : 'text/csv',
        ]);
    }

    /**
     * @param  resource  $handle
     */
    private function writeCsv(Builder $query, $handle): int
    {
        fputcsv($handle, [
            'id',
            'event_type',
            'bundle_id',
            'bundle_slug',
            'file_id',
            'actor_type',
            'actor_id',
            'actor_username',
            'recipient_email',
            'ip',
            'user_agent',
            'metadata',
            'created_at',
        ]);

        $count = 0;

        foreach ($this->iterateLogs($query) as $log) {
            fputcsv($handle, $this->csvRow($log));
            $count++;
        }

        return $count;
    }

    /**
     * @param  resource  $handle
     */
    private function writeJson(Builder $query, $handle): int
    {
        fwrite($handle, "[\n");

        $count = 0;
        $first = true;

        foreach ($this->iterateLogs($query) as $log) {
            if (! $first) {
                fwrite($handle, ",\n");
            }

            $encoded = json_encode($this->rowToArray($log), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            fwrite($handle, $encoded !== false ? $encoded : '{}');
            $first = false;
            $count++;
        }

        fwrite($handle, $count === 0 ? ']' : "\n]");

        return $count;
    }

    /**
     * @return \Generator<int, AuditLog>
     */
    private function iterateLogs(Builder $query): \Generator
    {
        foreach ((clone $query)->lazy(self::CHUNK_SIZE) as $log) {
            yield $log;
        }
    }

    /**
     * @return list<mixed>
     */
    private function csvRow(AuditLog $log): array
    {
        return [
            $log->id,
            $log->event_type->value,
            $log->bundle_id,
            $log->bundle?->slug,
            $log->file_id,
            $log->actor_type,
            $log->actor_id,
            $log->actor?->username,
            $log->recipient_email,
            $log->ip,
            $log->user_agent,
            $log->metadata !== null ? json_encode($log->metadata) : null,
            $log->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rowToArray(AuditLog $log): array
    {
        return [
            'id' => $log->id,
            'event_type' => $log->event_type->value,
            'bundle_id' => $log->bundle_id,
            'bundle_slug' => $log->bundle?->slug,
            'file_id' => $log->file_id,
            'actor_type' => $log->actor_type,
            'actor_id' => $log->actor_id,
            'actor_username' => $log->actor?->username,
            'recipient_email' => $log->recipient_email,
            'ip' => $log->ip,
            'user_agent' => $log->user_agent,
            'metadata' => $log->metadata,
            'created_at' => $log->created_at?->toIso8601String(),
        ];
    }
}
