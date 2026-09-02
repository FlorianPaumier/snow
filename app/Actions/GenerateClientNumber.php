<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ClientType;
use App\Models\Client;
use Illuminate\Support\Collection;

final readonly class GenerateClientNumber
{
    /**
     * Generate the next client number for the given type, e.g. "individual-0007".
     *
     * The sequence is max-based rather than count-based so numbers are never
     * reused after a client is deleted, and it is scoped per organization so
     * two companies never share numbering. Must run inside a transaction that
     * locks the clients table; the unique index is the final backstop.
     *
     * The max is computed in PHP rather than via max() so the row lock can
     * coexist with the aggregation — PostgreSQL rejects FOR UPDATE on
     * aggregate queries.
     */
    public function handle(ClientType $type, ?string $organizationId = null): string
    {
        $prefix = sprintf('%s-', $type->value);

        $query = Client::query()->withoutGlobalScopes()
            ->where('clientNumber', 'like', sprintf('%s%%', $prefix));

        if ($organizationId !== null) {
            $query->where('organization_id', $organizationId);
        }

        /** @var Collection<int, Client> $clients */
        $clients = $query->lockForUpdate()->get(['clientNumber']);

        $highest = $clients
            ->map(fn (Client $client): string => (string) $client->clientNumber)
            ->sortByDesc(fn (string $clientNumber): string => $clientNumber)
            ->first();

        $sequence = 1;

        if (is_string($highest)) {
            $sequence = (int) mb_substr($highest, mb_strlen($prefix)) + 1;
        }

        return sprintf('%s%04d', $prefix, $sequence);
    }
}
