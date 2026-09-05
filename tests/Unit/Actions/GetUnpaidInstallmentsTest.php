<?php

declare(strict_types=1);

use App\Actions\GetUnpaidInstallments;
use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Payment;
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
function unpaidClient(): ClientFactory
{
    $user = test()->user;

    return Client::factory()->forOrganization($user->organization);
}

it('returns no rows when every installment of the slot is paid', function (): void {
    $season = Season::fromYears(2026);

    $client = unpaidClient()->create();
    $contract = Contract::query()->create(['season' => $season->label, 'client_id' => $client->id]);
    Payment::query()->create(['contract_id' => $contract->id, 'slot' => PaymentSlot::SLOT_SECOND_INSTALLMENT, 'amount' => 20000, 'paid' => true, 'method' => 'cash', 'paid_at' => now()]);

    $rows = resolve(GetUnpaidInstallments::class)->handle($season, PaymentSlot::SLOT_SECOND_INSTALLMENT);

    expect($rows)->toBeEmpty();
});

it('lists unpaid installments of the requested slot with client details', function (): void {
    $season = Season::fromYears(2026);
    $action = resolve(GetUnpaidInstallments::class);

    $debtor = unpaidClient()->create([
        'lastName' => 'Tremblay',
        'firstName' => 'Marc',
        'address_street' => '1250 Rue Sainte-Catherine',
        'address_city' => 'Montréal',
        'phoneDay' => '514-555-0142',
        'email' => 'marc.tremblay@example.com',
    ]);

    $debtorContract = Contract::query()->create(['season' => $season->label, 'client_id' => $debtor->id]);
    Payment::query()->create(['contract_id' => $debtorContract->id, 'slot' => PaymentSlot::SLOT_SECOND_INSTALLMENT, 'amount' => 20000, 'paid' => false, 'due_date' => '2026-11-15', 'last_reminder_at' => '2026-09-01 09:00:00']);

    // Paid second installment: must not appear...
    $paidClient = unpaidClient()->create();
    $paidContract = Contract::query()->create(['season' => $season->label, 'client_id' => $paidClient->id]);
    Payment::query()->create(['contract_id' => $paidContract->id, 'slot' => PaymentSlot::SLOT_SECOND_INSTALLMENT, 'amount' => 20000, 'paid' => true, 'method' => 'debit', 'paid_at' => now()]);

    // Unpaid first installment of the same debtor: different slot, must not appear...
    Payment::query()->create(['contract_id' => $debtorContract->id, 'slot' => PaymentSlot::SLOT_FIRST_INSTALLMENT, 'amount' => 15000, 'paid' => false]);

    // Unpaid second installment in another season: must not appear...
    $oldContract = Contract::query()->create(['season' => Season::fromYears(2025)->label, 'client_id' => $debtor->id]);
    Payment::query()->create(['contract_id' => $oldContract->id, 'slot' => PaymentSlot::SLOT_SECOND_INSTALLMENT, 'amount' => 20000, 'paid' => false]);

    $rows = $action->handle($season, PaymentSlot::SLOT_SECOND_INSTALLMENT);

    expect($rows)->toHaveCount(1)
        ->and($rows->first())->toBe([
            'id' => $rows->first()['id'],
            'amount' => 20000,
            'slot' => PaymentSlot::SLOT_SECOND_INSTALLMENT,
            'contract_id' => $debtorContract->id,
            'client' => [
                'id' => $debtor->id,
                'displayName' => 'Marc Tremblay',
                'clientNumber' => $debtor->clientNumber,
                'address_street' => '1250 Rue Sainte-Catherine',
                'address_city' => 'Montréal',
                'phone' => '514-555-0142',
                'email' => 'marc.tremblay@example.com',
            ],
            'dueDate' => '2026-11-15',
            'lastReminderAt' => '2026-09-01',
        ]);
});

it('uses the company name for company clients', function (): void {
    $season = Season::fromYears(2026);

    $company = unpaidClient()->company()->create(['companyName' => 'Déneigement Nordik Inc.']);
    $contract = Contract::query()->create(['season' => $season->label, 'client_id' => $company->id]);
    Payment::query()->create(['contract_id' => $contract->id, 'slot' => PaymentSlot::SLOT_SECOND_INSTALLMENT, 'amount' => 20000, 'paid' => false]);

    $rows = resolve(GetUnpaidInstallments::class)->handle($season, PaymentSlot::SLOT_SECOND_INSTALLMENT);

    expect($rows->first()['client']['displayName'])->toBe('Déneigement Nordik Inc.');
});

it('shows the anonymized placeholder for anonymized clients instead of an empty name', function (): void {
    $season = Season::fromYears(2026);

    // Anonymization wipes the identity but keeps the unpaid skeleton, so
    // the follow-up list must render the placeholder rather than "  "...
    $anonymized = unpaidClient()->create(['anonymized_at' => now(), 'firstName' => '', 'lastName' => '']);
    $contract = Contract::query()->create(['season' => $season->label, 'client_id' => $anonymized->id]);
    Payment::query()->create(['contract_id' => $contract->id, 'slot' => PaymentSlot::SLOT_SECOND_INSTALLMENT, 'amount' => 20000, 'paid' => false]);

    $rows = resolve(GetUnpaidInstallments::class)->handle($season, PaymentSlot::SLOT_SECOND_INSTALLMENT);

    expect($rows)->toHaveCount(1)
        ->and($rows->first()['client']['displayName'])->toBe(__('clients.anonymized_name'))
        ->and($rows->first()['client']['displayName'])->not->toBe(' ');
});

it('excludes zero-amount installments from the unpaid list', function (): void {
    $season = Season::fromYears(2026);

    // Contracts always create all four payment slots; a slot left at $0 has
    // nothing to collect and must not pollute the follow-up list...
    $client = unpaidClient()->create();
    $contract = Contract::query()->create(['season' => $season->label, 'client_id' => $client->id]);
    Payment::query()->create(['contract_id' => $contract->id, 'slot' => PaymentSlot::SLOT_SECOND_INSTALLMENT, 'amount' => 0, 'paid' => false]);
    Payment::query()->create(['contract_id' => $contract->id, 'slot' => PaymentSlot::SLOT_DEPOSIT, 'amount' => 15000, 'paid' => false]);

    $second = resolve(GetUnpaidInstallments::class)->handle($season, PaymentSlot::SLOT_SECOND_INSTALLMENT);
    $deposit = resolve(GetUnpaidInstallments::class)->handle($season, PaymentSlot::SLOT_DEPOSIT);

    expect($second)->toBeEmpty()
        ->and($deposit)->toHaveCount(1)
        ->and($deposit->first()['amount'])->toBe(15000);
});

it('can target another slot such as the deposit', function (): void {
    $season = Season::fromYears(2026);

    $client = unpaidClient()->create();
    $contract = Contract::query()->create(['season' => $season->label, 'client_id' => $client->id]);
    Payment::query()->create(['contract_id' => $contract->id, 'slot' => PaymentSlot::SLOT_DEPOSIT, 'amount' => 10000, 'paid' => false]);

    $rows = resolve(GetUnpaidInstallments::class)->handle($season, PaymentSlot::SLOT_DEPOSIT);

    expect($rows)->toHaveCount(1)
        ->and($rows->first()['slot'])->toBe(PaymentSlot::SLOT_DEPOSIT);
});
