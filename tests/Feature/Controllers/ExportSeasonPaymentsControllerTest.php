<?php

declare(strict_types=1);

use App\Enums\PaymentMethod;
use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;

function seasonExportContract(User $user, string $season): Contract
{
    $client = Client::factory()->forOrganization($user->organization)->create();

    return Contract::factory()->forClient($client)->create(['season' => $season]);
}

/**
 * Parse the streamed CSV (BOM stripped) into header-keyed rows.
 *
 * @return list<array<string, string>>
 */
function exportedRows(string $content): array
{
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

    $lines = array_values(array_filter(explode("\n", $content), fn (string $line): bool => $line !== ''));

    $headers = str_getcsv($lines[0], escape: '\\');

    return array_map(
        fn (string $line): array => array_combine($headers, str_getcsv($line, escape: '\\')),
        array_slice($lines, 1),
    );
}

it('streams the season installments as a CSV download for admins', function (): void {
    $admin = User::factory()->admin()->create();
    $contract = seasonExportContract($admin, '2025-2026');
    $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 15000,
        'paid' => true,
        'method' => PaymentMethod::PAYMENT_CHEQUE,
        'paid_at' => '2025-11-15',
        'due_date' => '2025-11-01',
    ]);

    // Another season that must not leak into the filtered file...
    $previous = seasonExportContract($admin, '2024-2025');
    $previous->payments()->create([
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 12000,
        'paid' => false,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('payments.export', ['season' => '2025-2026']));

    $response->assertOk();

    $content = $response->streamedContent();

    expect($response->headers->get('Content-Type'))->toContain('text/csv')
        ->and($response->headers->get('Content-Disposition'))->toContain('Paiements-2025-2026.csv')
        // UTF-8 BOM so accented headers survive Excel's default encoding...
        ->and(str_starts_with((string) $content, "\xEF\xBB\xBF"))->toBeTrue();

    $rows = exportedRows($content);

    expect(count($rows))->toBe(1)
        ->and($rows[0]['Saison'])->toBe('2025-2026')
        ->and($rows[0]['Référence contrat'])->toBe($contract->id)
        ->and($rows[0]['Montant (CAD)'])->toBe('150.00')
        ->and($rows[0]['Payé'])->toBe('Oui')
        ->and($rows[0]['Méthode'])->toBe('Chèque')
        ->and($rows[0]['Référence contrat'])->not->toBe($previous->id);
});

it('exports every season when none is filtered', function (): void {
    $admin = User::factory()->admin()->create();
    seasonExportContract($admin, '2025-2026')->payments()->create([
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 15000,
        'paid' => false,
    ]);
    seasonExportContract($admin, '2024-2025')->payments()->create([
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 12000,
        'paid' => false,
    ]);

    $response = $this->actingAs($admin)->get(route('payments.export'));

    $response->assertOk();

    expect($response->headers->get('Content-Disposition'))->toContain('Paiements-toutes-saisons.csv')
        ->and(count(exportedRows($response->streamedContent())))->toBe(2);
});

it('refuses non-admin staff', function (): void {
    $worker = User::factory()->worker()->create();

    $this->actingAs($worker)
        ->get(route('payments.export'))
        ->assertForbidden();
});

it('requires guests to authenticate', function (): void {
    $this->get(route('payments.export'))
        ->assertRedirect(route('login'));
});

it('neutralizes spreadsheet formulas in exported client fields', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create([
        'firstName' => '=HYPERLINK("https://evil.test")',
        'lastName' => '@SUM(1+1)',
        'email' => '+malicious@example.com',
    ]);
    $contract = Contract::factory()->forClient($client)->create(['season' => '2025-2026']);
    $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 10000,
        'paid' => false,
    ]);

    $response = $this->actingAs($admin)->get(route('payments.export', ['season' => '2025-2026']));
    $row = exportedRows($response->streamedContent())[0];

    expect($row['Client'])->toStartWith("'")
        ->and($row['Courriel'])->toBe("'+malicious@example.com");
});

it('validates the season against the seasons that have contracts', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('payments.export', ['season' => '1999-2000']))
        ->assertSessionHasErrors('season');
});
