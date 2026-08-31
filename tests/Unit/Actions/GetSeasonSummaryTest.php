<?php

declare(strict_types=1);

use App\Actions\GetSeasonSummary;
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
function summaryClient(): Database\Factories\ClientFactory
{
    $user = test()->user;

    return Client::factory()->forOrganization($user->organization);
}

it('summarizes an empty season', function (): void {
    $summary = resolve(GetSeasonSummary::class)->handle(Season::fromYears(2030));

    expect($summary)->toBe([
        'revenue' => 0,
        'collected' => 0,
        'outstanding' => 0,
        'contracts_count' => 0,
    ]);
});

it('summarizes revenue, collected and outstanding for a season', function (): void {
    $season = Season::fromYears(2026);
    $otherSeason = Season::fromYears(2025);

    $paidClient = summaryClient()->create();
    $partialClient = summaryClient()->create();
    $unpaidClient = summaryClient()->create();

    // Fully paid contract: 500.00 revenue, 500.00 collected...
    $paidContract = Contract::query()->create(['season' => $season->label, 'client_id' => $paidClient->id]);
    ContractService::query()->create(['contract_id' => $paidContract->id, 'label' => 'Déneigement entrée', 'amount' => 50000, 'position' => 1]);
    Payment::query()->create(['contract_id' => $paidContract->id, 'slot' => PaymentSlot::SLOT_FIRST_INSTALLMENT, 'amount' => 30000, 'paid' => true, 'method' => 'cash', 'paid_at' => now()]);
    Payment::query()->create(['contract_id' => $paidContract->id, 'slot' => PaymentSlot::SLOT_SECOND_INSTALLMENT, 'amount' => 20000, 'paid' => true, 'method' => 'cash', 'paid_at' => now()]);

    // Partially paid contract: 400.00 revenue, only 150.00 collected...
    $partialContract = Contract::query()->create(['season' => $season->label, 'client_id' => $partialClient->id]);
    ContractService::query()->create(['contract_id' => $partialContract->id, 'label' => 'Déneigement stationnement', 'amount' => 40000, 'position' => 1]);
    Payment::query()->create(['contract_id' => $partialContract->id, 'slot' => PaymentSlot::SLOT_DEPOSIT, 'amount' => 15000, 'paid' => true, 'method' => 'cheque', 'paid_at' => now()]);
    Payment::query()->create(['contract_id' => $partialContract->id, 'slot' => PaymentSlot::SLOT_SECOND_INSTALLMENT, 'amount' => 25000, 'paid' => false]);

    // Unpaid contract: 600.00 revenue, nothing collected...
    $unpaidContract = Contract::query()->create(['season' => $season->label, 'client_id' => $unpaidClient->id]);
    ContractService::query()->create(['contract_id' => $unpaidContract->id, 'label' => 'Déglaçage', 'amount' => 60000, 'position' => 1]);
    Payment::query()->create(['contract_id' => $unpaidContract->id, 'slot' => PaymentSlot::SLOT_FIRST_INSTALLMENT, 'amount' => 60000, 'paid' => false]);

    // Other-season data that must be ignored...
    $oldContract = Contract::query()->create(['season' => $otherSeason->label, 'client_id' => $paidClient->id]);
    ContractService::query()->create(['contract_id' => $oldContract->id, 'label' => 'Déneigement entrée', 'amount' => 90000, 'position' => 1]);
    Payment::query()->create(['contract_id' => $oldContract->id, 'slot' => PaymentSlot::SLOT_FIRST_INSTALLMENT, 'amount' => 90000, 'paid' => true, 'method' => 'virement', 'paid_at' => now()]);

    $summary = resolve(GetSeasonSummary::class)->handle($season);

    expect($summary)->toBe([
        'revenue' => 150000,
        'collected' => 65000,
        'outstanding' => 85000,
        'contracts_count' => 3,
    ]);
});
