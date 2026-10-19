<?php

declare(strict_types=1);

use App\Models\CatalogService;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;

it('lets an admin manage the service catalog from settings', function (): void {
    $admin = User::factory()->admin()->create();
    $page = loginViaUi($admin)->navigate('/settings/profile');
    $page->click('Catalogue des services')
        ->assertPathIs('/settings/services')
        ->fill('#new-service-label', 'Déneigement entrée')
        ->fill('#new-service-amount', '425.75')
        ->press('Ajouter le service')
        ->assertSeeIn('[data-sonner-toast]', 'Service enregistré.');

    $service = CatalogService::query()->where('organization_id', $admin->organization_id)->sole();
    expect($service->amount)->toBe(42575);
    $page->fill('#service-'.$service->id.'-label', 'Déneigement allée')
        ->fill('#service-'.$service->id.'-amount', '499.99')
        ->press('Enregistrer')
        ->assertSee('Déneigement allée');
    expect($service->refresh()->amount)->toBe(49999);

    $page->press('Archiver le service')
        ->assertSeeIn('[data-sonner-toast]', 'Service archivé.')
        ->assertMissing('@catalog-service-'.$service->id)
        ->assertNoJavascriptErrors();
    $this->assertSoftDeleted($service);
    settleForAccessibilityScan($page);
    $page->assertNoAccessibilityIssues(3);
});

it('copies catalog defaults into editable independent contract lines', function (): void {
    $admin = User::factory()->admin()->create();
    Client::factory()->forOrganization($admin->organization)->create(['firstName' => 'Jean', 'lastName' => 'Tremblay']);
    $service = CatalogService::factory()->forOrganization($admin->organization)->create(['label' => 'Déneigement entrée', 'amount' => 42575]);
    $page = loginViaUi($admin)->navigate('/contracts/create');
    $page->fill('#clientId', 'Jean')->keys('#clientId', ['ArrowDown'])->keys('#clientId', ['Enter']);
    $page->click('#catalog-service');
    $page->page()->getByRole('option', ['name' => 'Déneigement entrée', 'exact' => false])->click();
    $page->assertValue('#services-0-label', 'Déneigement entrée')
        ->assertValue('#services-0-amount', '425.75')
        ->fill('#services-0-label', 'Allée personnalisée')
        ->fill('#services-0-amount', '500.25');
    $page->click('#catalog-service');
    $page->page()->getByRole('option', ['name' => 'Déneigement entrée', 'exact' => false])->click();
    $page->assertValue('#services-1-label', 'Déneigement entrée')
        ->assertValue('#services-1-amount', '425.75')
        ->fill('#payments-deposit', '926.00')
        ->press('Créer le contrat')
        ->assertSeeIn('[data-sonner-toast]', 'Contrat créé.');
    $contract = Contract::query()->where('organization_id', $admin->organization_id)->sole();
    expect($contract->services->pluck('amount')->all())->toBe([50025, 42575]);
    $service->update(['label' => 'Nouveau tarif', 'amount' => 60000]);
    $service->delete();
    $page->navigate('/contracts/'.$contract->id.'/edit')
        ->assertValue('#services-0-label', 'Allée personnalisée')
        ->assertValue('#services-0-amount', '500.25')
        ->assertValue('#services-1-amount', '425.75')
        ->assertMissing('#catalog-service')
        ->assertNoJavascriptErrors();
});

it('hides the service catalog settings from staff', function (): void {
    $worker = User::factory()->worker()->create();
    loginViaUi($worker)->navigate('/settings/profile')->assertDontSee('Catalogue des services');
});
