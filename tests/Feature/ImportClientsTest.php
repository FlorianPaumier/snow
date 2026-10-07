<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the client import page for client managers and protects the csv template', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('clients.import'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->component('clients/import'));
    $template = $this->actingAs($admin)->get(route('clients.import.template'));

    expect($template->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8')->streamedContent())
        ->toContain('type;client_number;company_name;first_name;last_name;email;daytime_phone;cell_phone;street;city;postal_code;country;remark')
        ->not->toContain('clients.import_fields');
});

it('downloads client csv template with stable headers in every locale', function (string $locale): void {
    $admin = User::factory()->admin()->create();

    $template = $this->actingAs($admin)
        ->withUnencryptedCookie('locale', $locale)
        ->get(route('clients.import.template'))
        ->assertOk()
        ->streamedContent();

    expect($template)
        ->toContain('type;client_number;company_name;first_name;last_name;email;daytime_phone;cell_phone;street;city;postal_code;country;remark')
        ->not->toContain('Prénom')
        ->not->toContain('First name')
        ->not->toContain('clients.import_fields');
})->with([
    'French' => ['fr'],
    'English' => ['en'],
]);

it('previews mapped client csv rows and imports them through the standard action', function (): void {
    Event::fake();
    $admin = User::factory()->admin()->create();
    $csv = file_get_contents(base_path('tests/Fixtures/clients-example.csv'));
    expect($csv)->not->toBeFalse();
    $file = UploadedFile::fake()->createWithContent('clients.csv', $csv);
    $mapping = [
        'type' => 'type', 'clientNumber' => 'client_number', 'companyName' => 'company_name', 'firstName' => 'first_name', 'lastName' => 'last_name', 'email' => 'email',
        'phoneDay' => 'daytime_phone', 'cellPhone' => 'cell_phone', 'addressStreet' => 'street', 'addressCity' => 'city',
        'addressPostalCode' => 'postal_code', 'addressCountry' => 'country', 'remark' => 'remark',
    ];

    $this->actingAs($admin)->post(route('clients.import.store'), ['file' => $file, 'mapping' => $mapping, 'mode' => 'preview'])
        ->assertRedirect(route('clients.import'));

    $this->get(route('clients.import'))->assertInertia(fn (Assert $page): Assert => $page
        ->component('clients/import')
        ->where('importPreview.rows.0.values.firstName', 'Jean')
        ->has('importPreview.rows', 5)
        ->where('importPreview.rows.0.errors', [])
        ->where('importPreview.rows.1.errors', [])
        ->where('importPreview.rows.2.errors', [])
        ->where('importPreview.rows.3.errors', [])
        ->where('importPreview.rows.4.errors', []));

    $this->post(route('clients.import.store'), ['csv' => base64_encode($csv), 'mapping' => $mapping, 'mode' => 'import'])
        ->assertRedirect(route('clients.index'));

    expect(Client::query()->where('email', 'jean.tremblay@example.com')->count())->toBe(1)
        ->and(Client::query()->count())->toBe(5);
});

it('automatically maps standard client csv headers on the first preview', function (): void {
    $admin = User::factory()->admin()->create();
    $csv = file_get_contents(base_path('tests/Fixtures/clients-example.csv'));
    $file = UploadedFile::fake()->createWithContent('clients.csv', $csv);
    $mapping = array_fill_keys(['type', 'clientNumber', 'companyName', 'firstName', 'lastName', 'email', 'phoneDay', 'cellPhone', 'addressStreet', 'addressCity', 'addressPostalCode', 'addressCountry', 'remark'], '');

    $this->actingAs($admin)->post(route('clients.import.store'), ['file' => $file, 'mapping' => $mapping, 'mode' => 'preview'])
        ->assertRedirect(route('clients.import'));

    $this->get(route('clients.import'))->assertInertia(fn (Assert $page): Assert => $page
        ->where('importPreview.mapping.firstName', 'first_name')
        ->where('importPreview.mapping.clientNumber', 'client_number')
        ->where('importPreview.mapping.addressPostalCode', 'postal_code')
        ->where('importPreview.rows.0.values.firstName', 'Jean')
        ->where('importPreview.rows.0.errors', []));
});

it('auto matches client csv headers regardless of case and separators', function (): void {
    $admin = User::factory()->admin()->create();
    $csv = "Client Number;First Name;Last Name;Email Address;Daytime Phone;Street;City;Postal Code;Country\nindividual-0001;Jean;Tremblay;jean@example.com;5145550101;123 Main;Montreal;H2X1L4;Canada\n";
    $file = UploadedFile::fake()->createWithContent('clients.csv', $csv);
    $mapping = array_fill_keys(['type', 'clientNumber', 'companyName', 'firstName', 'lastName', 'email', 'phoneDay', 'cellPhone', 'addressStreet', 'addressCity', 'addressPostalCode', 'addressCountry', 'remark'], '');

    $this->actingAs($admin)->post(route('clients.import.store'), ['file' => $file, 'mapping' => $mapping, 'mode' => 'preview'])->assertRedirect(route('clients.import'));

    $this->get(route('clients.import'))->assertInertia(fn (Assert $page): Assert => $page
        ->where('importPreview.mapping.clientNumber', 'Client Number')
        ->where('importPreview.mapping.firstName', 'First Name')
        ->where('importPreview.mapping.addressPostalCode', 'Postal Code')
        ->where('importPreview.rows.0.values.firstName', 'Jean')
        ->where('importPreview.rows.0.errors', []));
});

it('does not import client csv rows that fail validation', function (): void {
    $admin = User::factory()->admin()->create();
    $csv = "Name,Email,Phone,Street,City,Postal,Country\nJean,invalid,555,Street,City,Postal,Canada\n";
    $mapping = ['type' => '', 'companyName' => '', 'firstName' => 'Name', 'lastName' => '', 'email' => 'Email', 'phoneDay' => 'Phone', 'cellPhone' => '', 'addressStreet' => 'Street', 'addressCity' => 'City', 'addressPostalCode' => 'Postal', 'addressCountry' => 'Country', 'remark' => ''];

    $this->actingAs($admin)->post(route('clients.import.store'), ['csv' => base64_encode($csv), 'mapping' => $mapping, 'mode' => 'import'])
        ->assertSessionHasErrors('file');

    expect(Client::query()->count())->toBe(0);
});

it('requires a client number in every client csv row', function (): void {
    $admin = User::factory()->admin()->create();
    $csv = "type;client_number;company_name;first_name;last_name;email;daytime_phone;cell_phone;street;city;postal_code;country;remark\nindividual;;Jean;Tremblay;jean@example.com;5145550101;;123 Main;Montreal;H2X1L4;Canada;\n";
    $file = UploadedFile::fake()->createWithContent('clients.csv', $csv);
    $mapping = array_fill_keys(['type', 'clientNumber', 'companyName', 'firstName', 'lastName', 'email', 'phoneDay', 'cellPhone', 'addressStreet', 'addressCity', 'addressPostalCode', 'addressCountry', 'remark'], '');

    $this->actingAs($admin)->post(route('clients.import.store'), ['file' => $file, 'mapping' => $mapping, 'mode' => 'preview'])->assertRedirect(route('clients.import'));
    $this->get(route('clients.import'))->assertInertia(fn (Assert $page): Assert => $page->where('importPreview.rows.0.errors', fn ($errors): bool => $errors->isNotEmpty()));
});
