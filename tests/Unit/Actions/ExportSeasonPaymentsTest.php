<?php

declare(strict_types=1);

use App\Actions\ExportSeasonPayments;
use App\Enums\PaymentMethod;
use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

// The organization scope requires an authenticated user to see any data;
// contracts must belong to that user's organization to be exported...
beforeEach(function (): void {
    $this->user = User::factory()->create();

    Auth::login($this->user);
});

function exportClient(): Client
{
    return Client::factory()->forOrganization(test()->user->organization)->create();
}

function exportContract(Client $client, string $season): Contract
{
    return Contract::factory()->forClient($client)->create(['season' => $season]);
}

it('exports one row per installment in slot order with client and contract details', function (): void {
    $client = exportClient();
    $contract = exportContract($client, '2025-2026');

    $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_FIRST_INSTALLMENT,
        'amount' => 20000,
        'paid' => true,
        'method' => PaymentMethod::PAYMENT_VIREMENT,
        'paid_at' => '2025-12-01',
        'due_date' => '2025-12-01',
    ]);
    $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 15000,
        'paid' => false,
        'due_date' => '2025-11-01',
    ]);

    $rows = iterator_to_array(resolve(ExportSeasonPayments::class)->handle('2025-2026'));

    expect(count($rows))->toBe(2);

    $deposit = array_combine(ExportSeasonPayments::headers(), $rows[0]);
    $installment = array_combine(ExportSeasonPayments::headers(), $rows[1]);

    expect($deposit['Échéance'])->toBe('Dépôt')
        ->and($deposit['Saison'])->toBe('2025-2026')
        ->and($deposit['Référence contrat'])->toBe($contract->id)
        ->and($deposit['Nº client'])->toBe($client->clientNumber)
        ->and($deposit['Client'])->toBe($client->displayName())
        ->and($deposit['Type'])->toBe('Particulier')
        ->and($deposit['Adresse'])->toBe($client->address_street)
        ->and($deposit['Ville'])->toBe($client->address_city)
        ->and($deposit['Courriel'])->toBe($client->email)
        ->and($deposit['Montant (CAD)'])->toBe('150.00')
        ->and($deposit['Payé'])->toBe('Non')
        ->and($deposit['Date de paiement'])->toBe('')
        ->and($deposit['Méthode'])->toBe('')
        ->and($installment['Échéance'])->toBe('1er versement')
        ->and($installment['Montant (CAD)'])->toBe('200.00')
        ->and($installment['Payé'])->toBe('Oui')
        ->and($installment['Date de paiement'])->toBe('2025-12-01')
        ->and($installment['Méthode'])->toBe('Virement');
});

it('exports company clients with their company name', function (): void {
    $client = Client::factory()->company()->forOrganization($this->user->organization)->create();
    $contract = exportContract($client, '2025-2026');
    $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 10000,
        'paid' => false,
    ]);

    $rows = iterator_to_array(resolve(ExportSeasonPayments::class)->handle('2025-2026'));

    $row = array_combine(ExportSeasonPayments::headers(), $rows[0]);

    expect($row['Client'])->toBe($client->displayName())
        ->and($row['Type'])->toBe('Entreprise');
});

it('filters by season and exports every season when none is given', function (): void {
    // Seed the older season first: its contract gets the smaller id, which
    // locks the id-ordered stream assertion below...
    $client = exportClient();
    $previous = exportContract($client, '2024-2025');
    $previous->payments()->create([
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 12000,
        'paid' => false,
    ]);
    $current = exportContract($client, '2025-2026');
    $current->payments()->create([
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 15000,
        'paid' => false,
    ]);

    $rows = iterator_to_array(resolve(ExportSeasonPayments::class)->handle('2024-2025'));
    $seasons = array_column(array_map(
        fn (array $row): array => array_combine(ExportSeasonPayments::headers(), $row),
        $rows,
    ), 'Saison');

    expect($seasons)->toBe(['2024-2025']);

    $allRows = iterator_to_array(resolve(ExportSeasonPayments::class)->handle());
    $allSeasons = array_column(array_map(
        fn (array $row): array => array_combine(ExportSeasonPayments::headers(), $row),
        $allRows,
    ), 'Saison');

    // Rows stream in id order (lazyById), not season order: adding a
    // season orderBy would silently skip rows once paging crosses a
    // chunk, since forPageAfterId keeps existing orders under its own
    // `id ASC` and pages by `WHERE id > lastId`. The exact sequence —
    // oldest id first, i.e. the older season — pins the decision...
    expect($allSeasons)->toBe(['2024-2025', '2025-2026']);
});

it("never exports another organization's payments", function (): void {
    $otherOrganization = Organization::factory()->create();
    $otherClient = Client::factory()->forOrganization($otherOrganization)->create();
    $otherContract = exportContract($otherClient, '2025-2026');
    $otherContract->payments()->create([
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 99900,
        'paid' => false,
    ]);

    $client = exportClient();
    $contract = exportContract($client, '2025-2026');
    $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 15000,
        'paid' => false,
    ]);

    $rows = iterator_to_array(resolve(ExportSeasonPayments::class)->handle('2025-2026'));

    expect(count($rows))->toBe(1);

    $row = array_combine(ExportSeasonPayments::headers(), $rows[0]);

    expect($row['Référence contrat'])->toBe($contract->id)
        ->and($row['Montant (CAD)'])->toBe('150.00');
});
