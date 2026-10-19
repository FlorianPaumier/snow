<?php

declare(strict_types=1);

use App\Jobs\SendContractEmail;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('confirms the recipient and queues one contract from its detail page', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create(['email' => 'recipient@example.com']);
    $contract = Contract::factory()->forClient($client)->create();
    Queue::fake();
    $page = loginViaUi($admin)->navigate('/contracts/'.$contract->id);
    $page->click('@send-contracts')->assertSee('recipient@example.com')->assertSee('Confirmer l’envoi des contrats');
    Queue::assertNothingPushed();
    $page->click('@confirm-send-contracts')->assertSeeIn('[data-sonner-toast]', '1 contrat(s) mis en file');
    Queue::assertPushed(SendContractEmail::class, fn (SendContractEmail $job): bool => $job->contractId === $contract->id);
    $page->assertSee('En file d’envoi')->assertNoJavascriptErrors();
});

it('queues a selection from the contracts list without emailing other rows', function (): void {
    $admin = User::factory()->admin()->create();
    $contracts = collect(['one@example.com', 'two@example.com', 'not-selected@example.com'])->map(function (string $email) use ($admin): Contract {
        $client = Client::factory()->forOrganization($admin->organization)->create(['email' => $email]);

        return Contract::factory()->forClient($client)->create();
    });
    Queue::fake();
    $page = loginViaUi($admin)->navigate('/contracts');
    $page->assertMissing('@send-contracts')->click('@select-contract-'.$contracts[0]->id)
        ->assertVisible('[data-test="contract-actions"] [data-test="send-contracts"]')
        ->click('@select-contract-'.$contracts[0]->id)->assertMissing('@send-contracts')
        ->click('@select-contract-'.$contracts[0]->id)->click('@select-contract-'.$contracts[1]->id)
        ->click('@send-contracts')->assertSee('one@example.com')->assertSee('two@example.com')->assertDontSee('not-selected@example.com')
        ->click('@confirm-send-contracts')->assertSeeIn('[data-sonner-toast]', '2 contrat(s) mis en file');
    Queue::assertPushed(SendContractEmail::class, 2);
    Queue::assertNotPushed(SendContractEmail::class, fn (SendContractEmail $job): bool => $job->contractId === $contracts[2]->id);
    $page->assertMissing('@send-contracts')->assertNoJavascriptErrors();
});

it('flags missing recipients and never enables confirmation', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create(['email' => null]);
    $contract = Contract::factory()->forClient($client)->create();
    Queue::fake();
    $page = loginViaUi($admin)->navigate('/contracts/'.$contract->id);
    $page->click('@send-contracts')->assertSee('Adresse courriel manquante')->assertButtonDisabled('@confirm-send-contracts');
    Queue::assertNothingPushed();
    settleForAccessibilityScan($page);
    $page->wait(1);
    $page->assertNoAccessibilityIssues(3)->assertNoJavascriptErrors();
});

it('does not expose contract sending to regular staff', function (): void {
    $user = User::factory()->create();
    $client = Client::factory()->forOrganization($user->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();
    $page = loginViaUi($user)->navigate('/contracts');
    $page->assertMissing('@send-contracts')->assertMissing('@select-all-contracts')
        ->navigate('/contracts/'.$contract->id)->assertMissing('@send-contracts');
});
