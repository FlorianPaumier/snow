<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use App\ValueObjects\Season;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

it('shows the contract import page and downloads a locale independent csv template', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('contracts.import'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->component('contract/import'));
    $template = $this->actingAs($admin)->get(route('contracts.import.template'))->assertOk()->streamedContent();

    expect($template)->toContain('client_number;season;service_label;service_amount;deposit_amount;deposit_due_date;first_amount;first_due_date;second_amount;second_due_date;balance_amount;balance_due_date');
});

it('previews and imports contracts linked by client number', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create(['clientNumber' => 'individual-0001']);
    $csv = str_replace('2026-2027', Season::current()->label, (string) file_get_contents(base_path('tests/Fixtures/contracts-example.csv')));
    $mapping = array_fill_keys(['clientNumber', 'season', 'serviceLabel', 'serviceAmount', 'depositAmount', 'depositDueDate', 'firstAmount', 'firstDueDate', 'secondAmount', 'secondDueDate', 'balanceAmount', 'balanceDueDate'], '');
    $file = UploadedFile::fake()->createWithContent('contracts.csv', $csv);

    $this->actingAs($admin)->post(route('contracts.import.store'), ['file' => $file, 'mapping' => $mapping, 'mode' => 'preview'])->assertRedirect(route('contracts.import'));
    $preview = $this->get(route('contracts.import'))->assertInertia(fn (Assert $page): Assert => $page
        ->component('contract/import')
        ->where('importPreview.mapping.clientNumber', 'client_number')
        ->where('importPreview.rows.0.errors', []));

    $this->post(route('contracts.import.store'), ['csv' => base64_encode($csv), 'mapping' => [
        'clientNumber' => 'client_number', 'season' => 'season', 'serviceLabel' => 'service_label', 'serviceAmount' => 'service_amount',
        'depositAmount' => 'deposit_amount', 'depositDueDate' => 'deposit_due_date', 'firstAmount' => 'first_amount', 'firstDueDate' => 'first_due_date',
        'secondAmount' => 'second_amount', 'secondDueDate' => 'second_due_date', 'balanceAmount' => 'balance_amount', 'balanceDueDate' => 'balance_due_date',
    ], 'mode' => 'import'])->assertRedirect(route('contracts.index'));

    $contract = Contract::query()->with(['services', 'payments'])->firstOrFail();
    expect($contract->client_id)->toBe($client->id)
        ->and($contract->season)->toBe(Season::current()->label)
        ->and($contract->services)->toHaveCount(1)
        ->and($contract->services->first()->amount)->toBe(10000)
        ->and($contract->payments)->toHaveCount(4)
        ->and($contract->payments->sum('amount'))->toBe(10000);
});

it('imports a contract fixture linked to a client created from the client fixture', function (): void {
    $admin = User::factory()->admin()->create();
    $clientCsv = (string) file_get_contents(base_path('tests/Fixtures/clients-example.csv'));
    $clientMapping = [
        'type' => 'type', 'clientNumber' => 'client_number', 'companyName' => 'company_name', 'firstName' => 'first_name', 'lastName' => 'last_name', 'email' => 'email',
        'phoneDay' => 'daytime_phone', 'cellPhone' => 'cell_phone', 'addressStreet' => 'street', 'addressCity' => 'city',
        'addressPostalCode' => 'postal_code', 'addressCountry' => 'country', 'remark' => 'remark',
    ];
    $this->actingAs($admin)->post(route('clients.import.store'), [
        'file' => UploadedFile::fake()->createWithContent('clients.csv', $clientCsv),
        'mapping' => $clientMapping,
        'mode' => 'import',
    ])->assertRedirect(route('clients.index'));

    $csv = str_replace('2026-2027', Season::current()->label, (string) file_get_contents(base_path('tests/Fixtures/contracts-example.csv')));
    $contractMapping = array_fill_keys(['clientNumber', 'season', 'serviceLabel', 'serviceAmount', 'depositAmount', 'depositDueDate', 'firstAmount', 'firstDueDate', 'secondAmount', 'secondDueDate', 'balanceAmount', 'balanceDueDate'], '');
    $file = UploadedFile::fake()->createWithContent('contracts.csv', $csv);

    $this->post(route('contracts.import.store'), ['file' => $file, 'mapping' => $contractMapping, 'mode' => 'preview'])->assertRedirect(route('contracts.import'));
    $this->get(route('contracts.import'))->assertInertia(fn (Assert $page): Assert => $page
        ->where('importPreview.mapping.clientNumber', 'client_number')
        ->where('importPreview.rows.0.errors', []));

    $this->post(route('contracts.import.store'), ['csv' => base64_encode($csv), 'mapping' => [
        'clientNumber' => 'client_number', 'season' => 'season', 'serviceLabel' => 'service_label', 'serviceAmount' => 'service_amount',
        'depositAmount' => 'deposit_amount', 'depositDueDate' => 'deposit_due_date', 'firstAmount' => 'first_amount', 'firstDueDate' => 'first_due_date',
        'secondAmount' => 'second_amount', 'secondDueDate' => 'second_due_date', 'balanceAmount' => 'balance_amount', 'balanceDueDate' => 'balance_due_date',
    ], 'mode' => 'import'])->assertRedirect(route('contracts.index'));

    $contract = Contract::query()->with('client')->firstOrFail();
    expect($contract->client->clientNumber)->toBe('individual-0001');
});

it('rejects unknown clients and duplicate seasons before importing any contracts', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create(['clientNumber' => 'individual-0001']);
    Contract::factory()->forClient($client)->create(['season' => Season::current()->next()->label]);
    $csv = str_replace('2026-2027', Season::current()->label, (string) file_get_contents(base_path('tests/Fixtures/contracts-example.csv')));
    $file = UploadedFile::fake()->createWithContent('contracts.csv', $csv);
    $mapping = array_fill_keys(['clientNumber', 'season', 'serviceLabel', 'serviceAmount', 'depositAmount', 'depositDueDate', 'firstAmount', 'firstDueDate', 'secondAmount', 'secondDueDate', 'balanceAmount', 'balanceDueDate'], '');

    $this->actingAs($admin)->post(route('contracts.import.store'), ['file' => $file, 'mapping' => $mapping, 'mode' => 'preview'])->assertRedirect(route('contracts.import'));
    $this->get(route('contracts.import'))->assertInertia(fn (Assert $page): Assert => $page->has('importPreview.rows.0.errors', fn (Assert $errors): Assert => $errors->etc()));
    expect(Contract::query()->count())->toBe(1);
});
