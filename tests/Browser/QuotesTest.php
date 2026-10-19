<?php

declare(strict_types=1);

use App\Actions\SendQuote;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('lets the office edit and send a draft quote', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();
    $quote = Quote::factory()->create(['organization_id' => $admin->organization_id, 'client_id' => $client->id]);
    $page = loginViaUi($admin);
    $page->navigate('/quotes')->assertSee('Soumissions')->click('Ouvrir la soumission')
        ->click('Modifier')->assertSee('Enregistrer la soumission')->press('Enregistrer la soumission')
        ->press('Envoyer par courriel')->assertSee('Envoyée')->assertNoJavascriptErrors();
    expect($quote->refresh()->status)->toBe('sent');
});

it('lets a homeowner accept the private offer without an account', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();
    $quote = Quote::factory()->create(['organization_id' => $admin->organization_id, 'client_id' => $client->id]);
    Mail::fake();
    resolve(SendQuote::class)->handle($admin, $quote);
    $page = visit($quote->refresh()->reviewUrl());
    $page->assertSee($admin->organization->name)->assertSee('Snow removal')
        ->check('accepted')->press('Accepter la soumission')->assertSee('Soumission acceptée')->assertNoJavascriptErrors();
    expect(Contract::query()->where('client_id', $client->id)->count())->toBe(1);
});
