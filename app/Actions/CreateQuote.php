<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final readonly class CreateQuote
{
    /** @param array{clientId: string, season: string, services: list<array{label: string, amount: int}>, payments: array<string, array{amount: int, dueDate: string|null}>} $data */
    public function handle(User $admin, array $data): Quote
    {
        Gate::forUser($admin)->authorize('manage-contracts');
        $client = Client::query()->where('organization_id', $admin->organization_id)->whereNotAnonymized()->findOrFail($data['clientId']);

        return Quote::query()->create(['organization_id' => $admin->organization_id, 'client_id' => $client->id,
            'season' => $data['season'], 'services' => $data['services'], 'payments' => $data['payments']]);
    }
}
