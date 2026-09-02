<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\GetSubscriptionAccess;
use App\Exceptions\OrganizationReadOnly;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks data mutations for organizations without valid access (expired
 * trial, no valid subscription, no active unlock). Reads always pass; the
 * billing routes stay open — they are the way out.
 */
final readonly class EnsureOrganizationNotReadOnly
{
    private const array SAFE_VERBS = ['GET', 'HEAD', 'OPTIONS'];

    public function __construct(private GetSubscriptionAccess $getSubscriptionAccess) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if (in_array($request->getMethod(), self::SAFE_VERBS, true)) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if (is_string($routeName) && str_starts_with($routeName, 'billing.')) {
            return $next($request);
        }

        $organization = $request->user()?->organization;

        if ($organization === null) {
            return $next($request);
        }

        $verdict = $this->getSubscriptionAccess->handle($organization);

        if ($verdict['readOnly']) {
            throw new OrganizationReadOnly(__('statuses.read_only_blocked'));
        }

        return $next($request);
    }
}
