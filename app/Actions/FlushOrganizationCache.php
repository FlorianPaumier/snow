<?php

declare(strict_types=1);

namespace App\Actions;

use App\Support\TenantCache;

/**
 * Invalidates the current (or given) organization's cached data after a
 * mutation, by rotating the organization's cache version token. Called
 * at the end of every write action so every read — analytics, list
 * pages, subscription data — re-seeds on the next request.
 */
final readonly class FlushOrganizationCache
{
    public function __construct(private TenantCache $cache) {}

    public function handle(?string $organizationId = null): void
    {
        $this->cache->flush($organizationId);
    }
}
