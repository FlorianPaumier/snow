<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Tour;
use App\Models\TourTemplate;
use App\Models\User;
use Illuminate\Support\Str;

it('lets an administrator save a reusable route and dispatch it for today', function (): void {
    $admin = User::factory()->admin()->create();
    $worker = User::factory()->worker()->forOrganization($admin->organization)->create();
    Client::factory()->forOrganization($admin->organization)->create(['firstName' => 'Jean', 'lastName' => 'Tremblay']);
    $page = loginViaUi($admin);
    $page->navigate('/tours')->click('Modèles et déploiement')->click('Nouveau modèle')
        ->fill('#name', 'Route du nord')->select('#worker_id', $worker->id)->press('Ajouter')
        ->press('Enregistrer le modèle')->assertSee('Route du nord')->check('Route du nord')
        ->press('Déployer les tournées')->assertSee('Route du nord')->assertSee('Prévue')->assertNoJavascriptErrors();
    expect(TourTemplate::query()->count())->toBe(1)->and(Tour::query()->count())->toBe(1)
        ->and(Tour::query()->sole()->worker_id)->toBe($worker->id);
});

it('lets the office remove an unavailable client and repair a template', function (): void {
    $admin = User::factory()->admin()->create();
    $worker = User::factory()->worker()->forOrganization($admin->organization)->create();
    $client = Client::factory()->forOrganization($admin->organization)->create(['firstName' => 'Jean', 'lastName' => 'Tremblay']);
    $template = TourTemplate::factory()->create(['organization_id' => $admin->organization_id, 'worker_id' => $worker->id,
        'client_ids' => [(string) Str::uuid()]]);
    $page = loginViaUi($admin);
    $page->navigate('/tour-templates/'.$template->id.'/edit')->assertSee('Client indisponible')
        ->press('Retirer · Client indisponible')->press('Ajouter')->press('Enregistrer le modèle')->assertNoJavascriptErrors();
    expect($template->refresh()->client_ids)->toBe([$client->id]);
});
