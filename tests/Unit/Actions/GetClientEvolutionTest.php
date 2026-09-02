<?php

declare(strict_types=1);

use App\Actions\GetClientEvolution;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use App\ValueObjects\Season;
use Database\Factories\ClientFactory;
use Illuminate\Support\Facades\Auth;

// The organization scope requires an authenticated user to see any data;
// clients must belong to that user's organization to be visible...
beforeEach(function (): void {
    $this->user = User::factory()->create();

    Auth::login($this->user);
});

/**
 * Client factory bound to the acting user's organization.
 */
function clientEvolutionClient(): ClientFactory
{
    $user = test()->user;

    return Client::factory()->forOrganization($user->organization);
}

it('returns no rows when there are no contracts', function (): void {
    expect(resolve(GetClientEvolution::class)->handle())->toBeEmpty();
});

it('counts new clients per first-contract season with a cumulative total', function (): void {
    $seasons = [
        Season::fromYears(2024),
        Season::fromYears(2025),
        Season::fromYears(2026),
    ];

    // Two clients started in the oldest season...
    $earlyClients = Client::factory()->count(2)->create();
    // Two more joined the middle season...
    $midClients = Client::factory()->count(2)->create();
    // One joined the current season...
    $newClient = clientEvolutionClient()->create();

    // Every client renews in later seasons; only the first contract matters...
    foreach ($earlyClients as $client) {
        foreach ($seasons as $season) {
            Contract::query()->create(['season' => $season->label, 'client_id' => $client->id]);
        }
    }

    foreach ($midClients as $client) {
        foreach ([$seasons[1], $seasons[2]] as $season) {
            Contract::query()->create(['season' => $season->label, 'client_id' => $client->id]);
        }
    }

    Contract::query()->create(['season' => $seasons[2]->label, 'client_id' => $newClient->id]);

    $rows = resolve(GetClientEvolution::class)->handle();

    expect($rows->all())->toBe([
        ['season' => '2024-2025', 'new_clients' => 2, 'total_clients' => 2],
        ['season' => '2025-2026', 'new_clients' => 2, 'total_clients' => 4],
        ['season' => '2026-2027', 'new_clients' => 1, 'total_clients' => 5],
    ]);
});

it('ignores clients with contracts in only a later season when computing the first season', function (): void {
    $season = Season::fromYears(2026);

    $client = clientEvolutionClient()->create();
    Contract::query()->create(['season' => $season->label, 'client_id' => $client->id]);

    $rows = resolve(GetClientEvolution::class)->handle();

    expect($rows->all())->toBe([
        ['season' => $season->label, 'new_clients' => 1, 'total_clients' => 1],
    ]);
});
