<?php

namespace App\Http\Middleware;

use App\Models\Bundle;
use App\Services\Audit;
use App\Services\BundleInvitationService;
use App\Services\RecipientAccess;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GuestAccess
{
    public function __construct(
        private readonly BundleInvitationService $invitationService,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(empty($request->route()->parameter('bundle')), 404);
        $bundle = $request->route()->parameters()['bundle'];
        abort_if(! is_a($bundle, Bundle::class), 404);

        if (! $bundle->isShareable()) {
            Audit::denied($bundle, 'bundle_not_shareable', 404);

            abort(404);
        }

        if (! empty($bundle->expires_at)) {
            if ($bundle->expires_at->isBefore(Carbon::now())) {
                Audit::denied($bundle, 'bundle_expired', 404);

                abort(404);
            }
        }

        if (($bundle->max_downloads ?? 0) > 0 && $bundle->downloads >= $bundle->max_downloads) {
            Audit::denied($bundle, 'max_downloads_exceeded', 404);

            abort(404);
        }

        if ($this->invitationService->usesInvitationMode($bundle)) {
            if (! RecipientAccess::isVerified($bundle)) {
                Audit::denied($bundle, 'recipient_not_verified', 403);

                abort(403);
            }

            return $next($request);
        }

        if (empty($request->auth)) {
            Audit::denied($bundle, 'missing_auth_token', 403);

            abort(403);
        }

        if ($bundle->preview_token !== $request->auth) {
            Audit::denied($bundle, 'invalid_auth_token', 403);

            abort(403);
        }

        return $next($request);
    }
}
