<?php

namespace App\Services;

use App\Enums\AuditEvent;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Bundle;
use App\Models\File;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class Audit
{
    /**
     * @param  array{
     *     bundle?: Bundle|null,
     *     file?: File|null,
     *     user?: User|null,
     *     actor_type?: string|null,
     *     actor_id?: int|null,
     *     recipient_email?: string|null,
     *     metadata?: array<string, mixed>|null,
     *     ip?: string|null,
     *     user_agent?: string|null,
     * }  $context
     */
    public static function log(AuditEvent|string $event, array $context = []): void
    {
        $eventType = $event instanceof AuditEvent ? $event : AuditEvent::from($event);

        $bundle = $context['bundle'] ?? null;
        $file = $context['file'] ?? null;
        $user = $context['user'] ?? null;
        $recipientEmail = isset($context['recipient_email'])
            ? strtolower((string) $context['recipient_email'])
            : null;

        [$actorType, $actorId] = self::resolveActor($context, $user, $recipientEmail);

        AuditLog::create([
            'event_type' => $eventType,
            'bundle_id' => $bundle?->id,
            'file_id' => $file?->id,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'recipient_email' => $recipientEmail,
            'ip' => $context['ip'] ?? request()?->ip(),
            'user_agent' => $context['user_agent'] ?? request()?->userAgent(),
            'metadata' => $context['metadata'] ?? null,
            'created_at' => now(),
        ]);
    }

    public static function denied(?Bundle $bundle, string $reason, int $status = 403, ?string $recipientEmail = null): void
    {
        self::log(AuditEvent::AccessDenied, [
            'bundle' => $bundle,
            'recipient_email' => $recipientEmail ?? ($bundle !== null ? RecipientAccess::emailFor($bundle) : null),
            'metadata' => [
                'reason' => $reason,
                'status' => $status,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{0: ?string, 1: ?int}
     */
    private static function resolveActor(array $context, ?User $user, ?string $recipientEmail): array
    {
        if (isset($context['actor_type'])) {
            return [
                $context['actor_type'],
                $context['actor_id'] ?? null,
            ];
        }

        if ($user !== null) {
            return [
                $user->hasRole(UserRole::Admin) ? 'admin' : 'user',
                $user->id,
            ];
        }

        if (Auth::check()) {
            /** @var User $authUser */
            $authUser = Auth::user();

            return [
                $authUser->hasRole(UserRole::Admin) ? 'admin' : 'user',
                $authUser->id,
            ];
        }

        if ($recipientEmail !== null) {
            return ['recipient', null];
        }

        return ['anonymous', null];
    }
}
