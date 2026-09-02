<?php

declare(strict_types=1);

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Per-organization cache with version-keyed invalidation.
 *
 * Every key embeds a per-organization version token (a UUID remembered
 * forever). Flushing an organization forgets only that token: all of its
 * existing entries become unreachable orphans that expire with their
 * TTL, and the next read seeds a fresh version. One call therefore
 * invalidates an organization's entire cache — including list pages
 * with un-enumerable filter combinations — without a cache store that
 * supports tags.
 *
 * The organization is resolved from the authenticated user, mirroring
 * the OrganizationScope that scopes the underlying queries.
 */
final class TenantCache
{
    /**
     * TTL for aggregate/analytics data: accurate to the hour.
     */
    public const int TTL_LONG = 3600;

    /**
     * TTL for list pages and select options: accurate to the quarter
     * hour, and short enough that orphaned entries garbage-collect
     * quickly after a flush.
     */
    public const int TTL_SHORT = 900;

    /**
     * Cache the result of $callback for the current organization.
     *
     * @template TValue
     *
     * @param  list<string>  $params  values distinguishing variants of the same logical entry (season, filters, page, ...)
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    public function remember(string $name, array $params, Closure $callback, ?int $ttl = null): mixed
    {
        $organizationId = $this->organizationId();

        return Cache::remember(
            $this->key($organizationId, $name, $params),
            $ttl ?? self::TTL_SHORT,
            $callback,
        );
    }

    /**
     * Invalidate every cached entry of the organization by rotating its
     * version token.
     */
    public function flush(?string $organizationId = null): void
    {
        $organizationId ??= $this->organizationId();

        if ($organizationId !== null) {
            Cache::forget($this->versionKey($organizationId));
        }
    }

    /**
     * Full key for an entry: tenant prefix, version token, then the
     * entry name and its parameters (hashed so long filter combos stay
     * key-friendly).
     *
     * @param  list<string>  $params
     */
    private function key(?string $organizationId, string $name, array $params): string
    {
        $version = $organizationId !== null
            ? $this->version($organizationId)
            // Cache-key-less contexts (unauthenticated marketing pages
            // never get here, but a stray call must not crash)...
            : 'global';

        $params = $params === []
            ? ''
            : ':'.hash('sha256', implode('|', $params));

        return sprintf('tenant:v%s:%s:%s%s', $version, $organizationId ?? 'anon', $name, $params);
    }

    /**
     * The organization's current cache version token.
     */
    private function version(string $organizationId): string
    {
        return Cache::rememberForever(
            $this->versionKey($organizationId),
            static fn (): string => (string) Str::uuid(),
        );
    }

    private function versionKey(string $organizationId): string
    {
        return sprintf('tenant:cache:version:%s', $organizationId);
    }

    /**
     * The current user's organization, or null outside an authenticated
     * (or organization-less) context.
     */
    private function organizationId(): ?string
    {
        $organizationId = Auth::user()?->organization_id;

        return is_string($organizationId) ? $organizationId : null;
    }
}
