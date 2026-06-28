<?php

namespace App\Services;

use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditExporter
{
    /**
     * @return Builder<AuditLog>
     */
    public function query(?Carbon $from = null, ?Carbon $to = null): Builder
    {
        return AuditLog::query()
            ->with(['bundle', 'actor'])
            ->when($from, fn (Builder $q) => $q->where('created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->where('created_at', '<=', $to->endOfDay()))
            ->orderBy('created_at');
    }

    public function exportToPath(string $path, string $format, ?Carbon $from = null, ?Carbon $to = null): int
    {
        $logs = $this->query($from, $to)->get();
        $content = $format === 'json'
            ? $this->toJson($logs)
            : $this->toCsv($logs);

        Storage::disk('local')->put($path, $content);

        return $logs->count();
    }

    public function downloadResponse(string $format, ?Carbon $from = null, ?Carbon $to = null): StreamedResponse
    {
        $filename = 'audit-export-'.now()->format('Y-m-d-His').'.'.$format;

        return response()->streamDownload(function () use ($format, $from, $to) {
            if ($format === 'json') {
                echo $this->toJson($this->query($from, $to)->get());

                return;
            }

            echo $this->toCsv($this->query($from, $to)->get());
        }, $filename, [
            'Content-Type' => $format === 'json' ? 'application/json' : 'text/csv',
        ]);
    }

    /**
     * @param  Collection<int, AuditLog>  $logs
     */
    private function toCsv(Collection $logs): string
    {
        $handle = fopen('php://temp', 'r+');

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

        foreach ($logs as $log) {
            fputcsv($handle, [
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
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv ?: '';
    }

    /**
     * @param  Collection<int, AuditLog>  $logs
     */
    private function toJson(Collection $logs): string
    {
        $rows = $logs->map(fn (AuditLog $log) => [
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
        ]);

        return json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '[]';
    }
}
