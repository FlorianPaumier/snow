<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contract;
use App\Support\TenantCache;
use Illuminate\Support\Collection;

final readonly class GetClientEvolution
{
    public function __construct(private TenantCache $cache) {}

    /**
     * Clients per season, based on the season of their first contract (when
     * they started doing business with the company): new clients per season
     * and the cumulative total, oldest season first.
     *
     * @return Collection<int, array{season: string, new_clients: int, total_clients: int}>
     */
    public function handle(): Collection
    {
        /** @var Collection<int, array{season: string, new_clients: int, total_clients: int}> */
        return $this->cache->remember(
            'client-evolution',
            [],
            fn (): Collection => $this->compute(),
            TenantCache::TTL_LONG,
        );
    }

    /**
     * @return Collection<int, array{season: string, new_clients: int, total_clients: int}>
     */
    private function compute(): Collection
    {
        /** @var Collection<string, int> $firstSeasons */
        $firstSeasons = Contract::query()
            ->toBase()
            ->selectRaw('min(season) as first_season')
            ->groupBy('client_id')
            ->pluck('first_season');

        /** @var array<string, int> $newClientsPerSeason */
        $newClientsPerSeason = $firstSeasons
            ->countBy()
            ->sortKeys()
            ->all();

        $total = 0;

        /** @var Collection<int, array{season: string, new_clients: int, total_clients: int}> */
        return collect($newClientsPerSeason)
            ->map(function (int $newClients, string $season) use (&$total): array {
                $total += $newClients;

                return [
                    'season' => $season,
                    'new_clients' => $newClients,
                    'total_clients' => $total,
                ];
            })
            ->values();
    }
}
