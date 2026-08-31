<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ClientType;
use App\Models\Client;

final readonly class GenerateClientNumber
{
    /**
     * Generate the next client number for the given type, e.g. "individual-0007".
     *
     * The sequence is max-based rather than count-based so numbers are never
     * reused after a client is deleted, and it is scoped per organization so
     * two companies never share numbering. Must run inside a transaction that
     * locks the clients table; the unique index is the final backstop.
     */
    public function handle(ClientType $type, ?string $organizationId = null): string
    {
        $prefix = sprintf('%s-', $type->value);

        $query = Client::withoutGlobalScopes()
            ->where('clientNumber', 'like', sprintf('%s%%', $prefix));

        if ($organizationId !== null) {
            $query->where('organization_id', $organizationId);
        }

        /** @var string|null $highest */
        $highest = $query->lockForUpdate()->max('clientNumber');

        $sequence = 1;

        if (is_string($highest)) {
            $sequence = (int) mb_substr($highest, mb_strlen($prefix)) + 1;
        }

        return sprintf('%s%04d', $prefix, $sequence);
    }
}
