<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Tour;
use App\Models\TourNotification;
use App\Models\User;
use Illuminate\Support\Facades\URL;

it('lets the office enable consent preview a generic message and send it once', function (): void {
    config(['queue.default' => 'database']);
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();
    $tour = Tour::factory()->create(['organization_id' => $admin->organization_id, 'started_at' => now(),
        'stops' => [['id' => $client->id, 'name' => 'Internal customer', 'address' => 'Internal address', 'remark' => null]]]);
    $page = loginViaUi($admin);
    $page->navigate('/clients/'.$client->id)->check('Recevoir les avis de service par courriel')->press('Enregistrer la préférence');
    $page->navigate('/tours/'.$tour->id)->assertSee('Notre équipe de service a commencé une opération.')
        ->check('J’ai vérifié ce message général et je souhaite l’envoyer aux clients inscrits de cette tournée interne.')
        ->press('Envoyer l’avis aux clients')->assertSee('En file: 1')->assertNoJavascriptErrors();
    expect($client->refresh()->route_notifications_enabled)->toBeTrue()->and(TourNotification::query()->count())->toBe(1);
});

it('lets a customer unsubscribe without showing internal company operations', function (): void {
    $client = Client::factory()->create();
    $client->forceFill(['route_notifications_enabled' => true])->save();
    $url = URL::signedRoute('service-updates.unsubscribe', ['organization' => $client->organization_id, 'client' => $client->id]);
    $page = visit($url);
    $page->assertSee($client->organization->name)->assertDontSee('Tournées')->assertDontSee($client->displayName())
        ->press('Ne plus recevoir les avis de service')->assertSee('Les avis de service par courriel sont désactivés.')
        ->assertNoJavascriptErrors();
    expect($client->refresh()->route_notifications_enabled)->toBeFalse();
});
