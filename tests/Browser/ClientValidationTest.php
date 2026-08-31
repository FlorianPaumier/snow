<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\User;

/**
 * Browser coverage for the client management pages: the phone required_without
 * rule must surface in the UI, and the pages must remain accessible.
 */
it('requires at least one phone number on create', function (): void {
    $admin = User::factory()->admin()->create();
    $page = loginViaUi($admin);

    $page->navigate('/clients/create');
    $page->wait(2);

    // Fill every required field except both phone numbers (neither input is
    // required client-side; the rule is a server-side required_without)...
    $page->fill('#firstName', 'Jean')
        ->fill('#lastName', 'Tremblay')
        ->fill('#email', 'jean@example.com')
        ->fill('#addressStreet', '1234 Rue Sainte-Catherine')
        ->fill('#addressCity', 'Montréal')
        ->fill('#addressPostalCode', 'H3G 1M7')
        ->fill('#addressCountry', 'Canada')
        ->press('Create client');

    $page->assertSee('The phone day field is required when cell phone is not present.')
        ->assertPathIs('/clients/create');
});

/**
 * Pick an option in a shadcn/radix Select (not a native <select>). The
 * options render in a portal; Playwright's actionability checks race the
 * portal's animations, so the option is clicked through the DOM directly
 * once the trigger has opened the list.
 */
function pickSelectOption(Pest\Browser\Api\AwaitableWebpage $page, string $trigger, string $option): void
{
    $page->click('#'.$trigger);
    $page->wait(1);

    $page->script(
        'Array.from(document.querySelectorAll("[role=option]"))'
        .'.find(o => o.innerText.trim() === '.json_encode($option).').click()',
    );
    $page->wait(1);
}

it('shows and hides the company name field with the client type', function (): void {
    $admin = User::factory()->admin()->create();
    $page = loginViaUi($admin);

    $page->navigate('/clients/create');
    $page->wait(2);

    // The company name input only exists for company-type clients...
    $page->assertMissing('#companyName');

    pickSelectOption($page, 'type', 'Company');
    $page->assertVisible('#companyName');

    pickSelectOption($page, 'type', 'Individual');
    $page->assertMissing('#companyName');
});

it('requires a company name for company clients', function (): void {
    $admin = User::factory()->admin()->create();
    $page = loginViaUi($admin);

    $page->navigate('/clients/create');
    $page->wait(2);

    pickSelectOption($page, 'type', 'Company');

    $page->fill('#companyName', ' ')
        ->fill('#firstName', 'Jean')
        ->fill('#lastName', 'Tremblay')
        ->fill('#email', 'jean@example.com')
        ->fill('#phoneDay', '514 555-0100')
        ->fill('#addressStreet', '1234 Rue Sainte-Catherine')
        ->fill('#addressCity', 'Montréal')
        ->fill('#addressPostalCode', 'H3G 1M7')
        ->fill('#addressCountry', 'Canada')
        ->press('Create client');

    $page->assertSee('The company name field is required when type is company.')
        ->assertPathIs('/clients/create');
});

it('prefills the edit form and keeps the client number immutable', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();
    $page = loginViaUi($admin);

    $page->navigate("/clients/{$client->id}/edit");
    $page->wait(2);

    $page->assertValue('#firstName', $client->firstName)
        ->assertValue('#email', $client->email)
        ->assertSee($client->clientNumber);
});

it('is accessible on the client index and create pages', function (): void {
    $admin = User::factory()->admin()->create();
    Client::factory()->forOrganization($admin->organization)->count(3)->create();
    $page = loginViaUi($admin);

    $page->navigate('/clients');
    $page->wait(2);
    settleForAccessibilityScan($page);
    $page->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();

    $page->navigate('/clients/create');
    $page->wait(2);
    settleForAccessibilityScan($page);
    $page->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();
});

it('is accessible on the client edit and show pages', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();
    $page = loginViaUi($admin);

    $page->navigate("/clients/{$client->id}/edit");
    $page->wait(2);
    settleForAccessibilityScan($page);
    $page->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();

    $page->navigate("/clients/{$client->id}");
    $page->wait(2);
    settleForAccessibilityScan($page);
    $page->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();
});
