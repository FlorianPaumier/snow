<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Tour;
use App\Models\User;

it('lets a worker start and finish a tour and consult the recorded clients', function (): void {
    $worker = User::factory()->worker()->create();
    $tour = Tour::factory()->create([
        'organization_id' => $worker->organization_id,
        'worker_id' => $worker->id,
        'name' => 'Tournée tempête',
        'stops' => [['id' => 'client-test', 'name' => 'Jean Tremblay', 'address' => '123 rue du Test, Montréal', 'remark' => 'Entrée arrière']],
    ]);

    $page = loginViaUi($worker);
    $page->assertSee('Jean Tremblay')->assertSee('Entrée arrière')
        ->press('Débuter ma tournée')->assertSee('Terminer ma tournée')
        ->press('Terminer ma tournée')->assertDontSee('Débuter ma tournée')
        ->assertSee('Historique')->click('Tournée tempête')->assertSee('Jean Tremblay')
        ->assertNoJavascriptErrors();

    expect($tour->refresh()->started_at)->not->toBeNull()
        ->and($tour->finished_at)->not->toBeNull();
});

it('lets an admin assign a client and create a tour from the form', function (): void {
    $admin = User::factory()->admin()->create();
    $worker = User::factory()->worker()->forOrganization($admin->organization)->create();
    Client::factory()->forOrganization($admin->organization)->create(['firstName' => 'Jean', 'lastName' => 'Tremblay']);
    $page = loginViaUi($admin);
    $page->navigate('/tours/create')->fill('#name', 'Tournée du matin')
        ->select('#worker_id', $worker->id)->press('Ajouter')->press('Créer la tournée')
        ->assertSee('Tournée du matin')->assertSee('Jean Tremblay')->assertNoJavascriptErrors();

    expect(Tour::query()->where('name', 'Tournée du matin')->firstOrFail()->worker_id)->toBe($worker->id);
});
