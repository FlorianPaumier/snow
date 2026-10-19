<?php

declare(strict_types=1);

use App\Actions\GeocodeTourAddress;
use App\Actions\ImportContractsFromCsvAction;
use App\Actions\ImportUsersFromCsvAction;
use App\Actions\OptimizeTour;
use App\Actions\PrepareSeasonRenewals;
use App\Actions\SendPaymentReminder;
use App\Actions\ValidateClientCsv;
use App\Actions\ValidateContractCsvAction;
use App\Mail\OrganizationInviteCodeMail;
use App\Models\Client;
use App\Models\Contract;
use App\Models\OrganizationInvite;
use App\Models\Payment;
use App\Models\Tour;
use App\Models\User;
use App\ValueObjects\Season;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

it('renders the invitation code with its expiry and registration link', function (): void {
    $mail = new OrganizationInviteCodeMail('ABCD123456', '2026-10-30');
    expect($mail->render())->toContain('ABCD123456', '2026-10-30', route('register'));
});

it('allows only the intended recipient to redeem a live unclaimed invitation', function (string $state, bool $expected): void {
    $invite = OrganizationInvite::factory()->create(['email' => 'recipient@example.com',
        'expires_at' => $state === 'expired' ? now()->subMinute() : now()->addDay(),
        'claimed_at' => $state === 'claimed' ? now() : null]);
    expect($invite->isRedeemableBy($state === 'other' ? 'other@example.com' : 'RECIPIENT@EXAMPLE.COM'))->toBe($expected);
})->with(['live' => ['live', true], 'expired' => ['expired', false], 'claimed' => ['claimed', false], 'other recipient' => ['other', false]]);

it('handles an empty CSV without inventing records', function (string $action): void {
    expect(resolve($action)->handle('', []))->toBe(['headers' => [], 'mapping' => [], 'rows' => []]);
})->with([ValidateClientCsv::class, ValidateContractCsvAction::class]);

it('skips blank CSV lines and preserves the source row number', function (string $action): void {
    $result = resolve($action)->handle("name\n\nInvalid row\n", []);
    expect($result['rows'])->toHaveCount(1)->and($result['rows'][0]['row'])->toBe(3)
        ->and($result['rows'][0]['errors'])->not->toBeEmpty();
})->with([ValidateClientCsv::class, ValidateContractCsvAction::class]);

it('rejects contract CSV rows that violate financial or client constraints', function (string $issue): void {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    $client = Client::factory()->forOrganization($admin->organization)->create();
    $season = Season::current()->label;
    if ($issue === 'anonymized') {
        $client->forceFill(['anonymized_at' => now()])->save();
    }

    if ($issue === 'duplicate') {
        Contract::factory()->forClient($client)->create(['season' => $season]);
    }

    $values = array_fill_keys(ValidateContractCsvAction::FIELDS, '');
    $values = array_replace($values, ['clientNumber' => $client->clientNumber,
        'season' => $issue === 'invalid season' ? 'invalid' : $season, 'serviceLabel' => 'Snow',
        'serviceAmount' => $issue === 'totals' ? '999' : '40000', 'depositAmount' => '10000',
        'firstAmount' => '10000', 'secondAmount' => '10000', 'balanceAmount' => '10000']);
    $csv = implode(',', array_keys($values))."\n".implode(',', $values)."\n";
    $mapping = array_combine(array_keys($values), array_keys($values));
    $result = resolve(ValidateContractCsvAction::class)->handle($csv, $mapping);
    $message = match ($issue) {
        'anonymized' => __('clients.anonymized_no_new_contract'),
        'duplicate' => __('contracts.duplicate_season'),
        'totals' => __('contracts.totals_mismatch'),
        default => __('validation.regex', ['attribute' => 'season']),
    };
    expect($result['rows'][0]['errors'])->toContain($message);
    $before = Contract::query()->count();
    expect(fn () => resolve(ImportContractsFromCsvAction::class)->handle($csv, $mapping))->toThrow(ValidationException::class);
    expect(Contract::query()->count())->toBe($before);
})->with(['invalid season', 'anonymized', 'duplicate', 'totals']);

it('skips blank user CSV rows and refuses existing account emails', function (): void {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    $result = resolve(ImportUsersFromCsvAction::class)->handle("name,email,role\n\nDuplicate,{$admin->email},worker\n",
        ['name' => 'name', 'email' => 'email', 'role' => 'role'], true);
    expect($result['rows'])->toHaveCount(1)->and($result['rows'][0]['errors'])->not->toBeEmpty()
        ->and($admin->fresh()->name)->toBe($admin->name)
        ->and(User::query()->count())->toBe(1);
});

it('formats payment reminder timing from the current due date', function (int $offset, string $phrase): void {
    $payment = Payment::factory()->create(['due_date' => today()->addDays($offset)]);
    $details = resolve(SendPaymentReminder::class)->details($payment);
    expect($details['duePhrase'])->toBe($phrase);
})->with(['today' => [0, 'échoit aujourd’hui'], 'late' => [-2, 'en retard de 2 jours'], 'future' => [3, 'à régler d’ici 3 jours']]);

it('reports unavailable geocoding without saving a bogus result', function (int $status): void {
    config(['services.openrouteservice.key' => 'test-key']);
    Http::fake(['*/geocode/search*' => Http::response([], $status)]);
    expect(fn () => resolve(GeocodeTourAddress::class)->handle('Unknown street', 'organization'))
        ->toThrow(ValidationException::class, __('tours.optimization_unavailable'));
})->with([401, 503]);

it('refuses to optimize a started or oversized tour before sending addresses', function (string $reason): void {
    $admin = User::factory()->admin()->create();
    $worker = User::factory()->worker()->forOrganization($admin->organization)->create();
    $this->actingAs($admin);
    $tour = Tour::factory()->for($admin->organization)->for($worker, 'worker')->create(['stops' => [['id' => 'stop-1', 'name' => 'Client', 'address' => 'Street', 'remark' => null]]]);
    if ($reason === 'started') {
        $tour->forceFill(['started_at' => now()])->save();
    } else {
        config(['services.openrouteservice.max_stops' => 0]);
    }

    expect(fn () => resolve(OptimizeTour::class)->handle($admin, $tour))->toThrow(ValidationException::class);
    Http::assertNothingSent();
})->with(['started', 'oversized']);

it('rejects malformed renewal seasons when the action is called directly', function (): void {
    $admin = User::factory()->admin()->create();
    expect(fn () => resolve(PrepareSeasonRenewals::class)->handle($admin, 'not-a-season', Season::current()->label, []))
        ->toThrow(ValidationException::class, __('renewals.invalid_season'));
});
