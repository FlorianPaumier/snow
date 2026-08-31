<?php

declare(strict_types=1);

use App\Actions\GetSeasonRevenueRecap;
use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractService;
use App\Models\Payment;
use App\Models\User;
use App\ValueObjects\Season;
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
function recapClient(): Database\Factories\ClientFactory
{
    $user = test()->user;

    return Client::factory()->forOrganization($user->organization);
}

it('returns no rows when there are no contracts', function (): void {
    expect(resolve(GetSeasonRevenueRecap::class)->handle())->toBeEmpty();
});

it('recaps every season, newest first', function (): void {
    $current = Season::fromYears(2026);
    $previous = Season::fromYears(2025);

    $client = recapClient()->create();

    foreach ([$current, $previous] as $season) {
        $contract = Contract::query()->create(['season' => $season->label, 'client_id' => $client->id]);
        ContractService::query()->create(['contract_id' => $contract->id, 'label' => 'Déneigement entrée', 'amount' => 50000, 'position' => 1]);
        Payment::query()->create(['contract_id' => $contract->id, 'slot' => PaymentSlot::SLOT_FIRST_INSTALLMENT, 'amount' => 25000, 'paid' => true, 'method' => 'cash', 'paid_at' => now()]);
        Payment::query()->create(['contract_id' => $contract->id, 'slot' => PaymentSlot::SLOT_BALANCE, 'amount' => 25000, 'paid' => false]);
    }

    $rows = resolve(GetSeasonRevenueRecap::class)->handle();

    expect($rows)->toHaveCount(2)
        ->and($rows->first())->toBe([
            'season' => $current->label,
            'revenue' => 50000,
            'collected' => 25000,
            'outstanding' => 25000,
            'contracts_count' => 1,
        ])
        ->and($rows->last()['season'])->toBe($previous->label);
});

it('aggregates multiple contracts of the same season into one row', function (): void {
    $season = Season::fromYears(2026);
    // A client signs one contract per season, so same-season aggregation is
    // exercised across two clients...
    $clients = [recapClient()->create(), recapClient()->create()];

    foreach ($clients as $client) {
        $contract = Contract::query()->create(['season' => $season->label, 'client_id' => $client->id]);
        ContractService::query()->create(['contract_id' => $contract->id, 'label' => 'Déneigement entrée', 'amount' => 30000, 'position' => 1]);
        Payment::query()->create(['contract_id' => $contract->id, 'slot' => PaymentSlot::SLOT_DEPOSIT, 'amount' => 10000, 'paid' => true, 'method' => 'debit', 'paid_at' => now()]);
    }

    $rows = resolve(GetSeasonRevenueRecap::class)->handle();

    expect($rows)->toHaveCount(1)
        ->and($rows->first()['revenue'])->toBe(60000)
        ->and($rows->first()['collected'])->toBe(20000)
        ->and($rows->first()['outstanding'])->toBe(40000)
        ->and($rows->first()['contracts_count'])->toBe(2);
});
