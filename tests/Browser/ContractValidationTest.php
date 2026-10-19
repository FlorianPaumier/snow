<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\User;

/**
 * Browser coverage for the contract pages: the live totals warning, the
 * balanced-payments server rule, and the locked paid installments on edit.
 */
it('warns when the totals do not match while creating a contract', function (): void {
    $admin = User::factory()->admin()->create();
    Client::factory()->forOrganization($admin->organization)->create();
    $page = loginViaUi($admin);

    $page->navigate('/contracts/create');
    $page->wait(2);

    // Services total 500.00 while the installments only cover the deposit
    // (150.00): the live totals banner must flag the mismatch...
    $page->fill('#services-0-label', 'Déneigement entrée')
        ->fill('#services-0-amount', '500')
        ->fill('#payments-deposit', '150');

    $page->assertVisible('@totals-warning')
        ->assertSee('Les totaux ne correspondent pas');

    // Balancing the installments clears the warning...
    $page->fill('#payments-first', '350')
        ->assertMissing('@totals-warning');
});

it('rejects an unbalanced contract with a server error', function (): void {
    $admin = User::factory()->admin()->create();
    Client::factory()->forOrganization($admin->organization)->create(['firstName' => 'Jean', 'lastName' => 'Tremblay']);
    $page = loginViaUi($admin);

    $page->navigate('/contracts/create');
    $page->wait(2);

    $page->fill('#clientId', 'Jean')
        ->keys('#clientId', ['ArrowDown'])
        ->keys('#clientId', ['Enter']);

    $page->fill('#services-0-label', 'Déneigement entrée')
        ->fill('#services-0-amount', '500')
        ->fill('#payments-deposit', '150')
        ->fill('#payments-first', '100')
        ->press('Créer le contrat');

    // The server-side withValidator hook adds a French error on payments...
    $page->assertSeeIn('[data-sonner-toast]', 'Le total des versements doit correspondre au total des services')
        ->assertPathIs('/contracts/create');
});

it('locks paid installments when editing a contract', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();
    Payment::factory()->forContract($contract)->deposit()->paid()->create();

    $page = loginViaUi($admin);

    $page->navigate(sprintf('/contracts/%s/edit', $contract->id));
    $page->wait(2);

    $page->assertDisabled('#payments-deposit')
        ->assertSee('Encaissé')
        ->assertEnabled('#payments-first');
});

it('is accessible on the contract pages', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();
    $page = loginViaUi($admin);

    $page->navigate('/contracts');
    $page->wait(2);
    settleForAccessibilityScan($page);
    $page->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();

    $page->navigate('/contracts/create');
    $page->wait(2);
    settleForAccessibilityScan($page);
    $page->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();

    $page->navigate('/contracts/'.$contract->id);
    $page->wait(2);
    settleForAccessibilityScan($page);
    $page->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();

    $page->navigate(sprintf('/contracts/%s/edit', $contract->id));
    $page->wait(2);
    settleForAccessibilityScan($page);
    $page->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();
});
