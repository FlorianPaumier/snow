<?php

declare(strict_types=1);

use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractService;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\User;
use App\ValueObjects\Season;

it('lets the office select a season, prepare a renewal, review it and send it', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create(['firstName' => 'Jean', 'lastName' => 'Tremblay']);
    $contract = Contract::factory()->forClient($client)->create(['season' => Season::current()->label]);
    ContractService::factory()->forContract($contract)->create(['amount' => 40000]);
    foreach (PaymentSlot::cases() as $slot) {
        Payment::factory()->forContract($contract)->paid()->slot($slot)->create(['amount' => 10000]);
    }

    $page = loginViaUi($admin);
    $page->navigate('/quotes')->click('Renouvellements')->assertSee('Jean Tremblay')
        ->check('Jean Tremblay')->press('Préparer les brouillons')->assertSee('Brouillon')
        ->click('Vérifier la soumission')->click('Modifier')
        ->assertSee('Vérification du renouvellement')->click('Prix et dates vérifiés')
        ->press('Enregistrer la soumission')->press('Envoyer par courriel')
        ->assertSee('Envoyée')->assertNoJavascriptErrors();
    $quote = Quote::query()->sole();
    expect($quote->status)->toBe('sent')->and($quote->source_contract_id)->toBe($contract->id)
        ->and($quote->renewal_reviewed_at)->not->toBeNull();
});
