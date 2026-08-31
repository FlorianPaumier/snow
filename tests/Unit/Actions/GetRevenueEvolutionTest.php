<?php

declare(strict_types=1);

use App\Actions\GetRevenueEvolution;
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
function revenueEvolutionClient(): Database\Factories\ClientFactory
{
    $user = test()->user;

    return Client::factory()->forOrganization($user->organization);
}

it('returns no rows when there are no contracts', function (): void {
    expect(resolve(GetRevenueEvolution::class)->handle())->toBeEmpty();
});

it('returns revenue per season, oldest first for the chart', function (): void {
    $oldest = Season::fromYears(2024);
    $newest = Season::fromYears(2026);

    $client = revenueEvolutionClient()->create();

    foreach ([$oldest, $newest] as $season) {
        $contract = Contract::query()->create(['season' => $season->label, 'client_id' => $client->id]);
        ContractService::query()->create(['contract_id' => $contract->id, 'label' => 'Déneigement entrée', 'amount' => 50000, 'position' => 1]);
        Payment::query()->create(['contract_id' => $contract->id, 'slot' => PaymentSlot::SLOT_FIRST_INSTALLMENT, 'amount' => 25000, 'paid' => true, 'method' => 'cash', 'paid_at' => now()]);
        Payment::query()->create(['contract_id' => $contract->id, 'slot' => PaymentSlot::SLOT_BALANCE, 'amount' => 25000, 'paid' => false]);
    }

    $rows = resolve(GetRevenueEvolution::class)->handle();

    expect($rows)->toHaveCount(2)
        ->and($rows->first())->toBe([
            'season' => $oldest->label,
            'revenue' => 50000,
            'collected' => 25000,
            'outstanding' => 25000,
        ])
        ->and($rows->last()['season'])->toBe($newest->label);
});

it('aggregates multiple contracts of the same season into one point', function (): void {
    $season = Season::fromYears(2026);

    // One contract per client per season (unique constraint)...
    $firstClient = revenueEvolutionClient()->create();
    $secondClient = revenueEvolutionClient()->create();

    foreach ([$firstClient, $secondClient] as $client) {
        $contract = Contract::query()->create(['season' => $season->label, 'client_id' => $client->id]);
        ContractService::query()->create(['contract_id' => $contract->id, 'label' => 'Déneigement entrée', 'amount' => 30000, 'position' => 1]);
        Payment::query()->create(['contract_id' => $contract->id, 'slot' => PaymentSlot::SLOT_DEPOSIT, 'amount' => 10000, 'paid' => true, 'method' => 'debit', 'paid_at' => now()]);
    }

    $rows = resolve(GetRevenueEvolution::class)->handle();

    expect($rows)->toHaveCount(1)
        ->and($rows->first()['revenue'])->toBe(60000)
        ->and($rows->first()['collected'])->toBe(20000)
        ->and($rows->first()['outstanding'])->toBe(40000);
});
