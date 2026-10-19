<?php

declare(strict_types=1);

use App\Actions\SaveCatalogService;
use App\Models\CatalogService;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use App\ValueObjects\Season;
use Database\Seeders\CatalogServiceSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Inertia\Testing\AssertableInertia as Assert;

it('lets admins save, edit and archive services in their own organization', function (): void {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->admin()->create();
    $this->actingAs($admin)->post(route('catalog-services.store'), [
        'label' => 'Driveway', 'amount' => 42500, 'organization_id' => $other->organization_id,
    ])->assertRedirect(route('catalog-services.index'))->assertSessionHas('status');
    $service = CatalogService::query()->sole();
    expect($service->organization_id)->toBe($admin->organization_id);

    $this->put(route('catalog-services.update', $service), ['label' => 'Walkway', 'amount' => 9999])
        ->assertRedirect(route('catalog-services.index'));
    expect($service->refresh()->label)->toBe('Walkway')->and($service->amount)->toBe(9999);
    $this->delete(route('catalog-services.destroy', $service))->assertRedirect(route('catalog-services.index'));
    $this->assertSoftDeleted($service);
    $this->get(route('catalog-services.index'))->assertInertia(fn (Assert $page): Assert => $page->has('services', 0));
    $this->put(route('catalog-services.update', $service), ['label' => 'Changed', 'amount' => 100])->assertNotFound();
});

it('only offers active tenant catalog entries on contracts and quotes', function (): void {
    $admin = User::factory()->admin()->create();
    $own = CatalogService::factory()->forOrganization($admin->organization)->create(['label' => 'Driveway']);
    CatalogService::factory()->forOrganization($admin->organization)->create()->delete();
    CatalogService::factory()->create(['label' => 'Other organization']);
    $client = Client::factory()->forOrganization($admin->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();
    $this->actingAs($admin)->get(route('catalog-services.index'))->assertInertia(fn (Assert $page): Assert => $page
        ->component('catalog-services/index')->has('services', 1)->where('services.0.id', $own->id));
    foreach ([route('contracts.create'), route('contracts.edit', $contract), route('quotes.create')] as $url) {
        $this->get($url)->assertInertia(fn (Assert $page): Assert => $page
            ->has('catalogServices', 1)->where('catalogServices.0.id', $own->id)->where('catalogServices.0.amount', 42500));
    }
});

it('keeps customized contract snapshots when catalog defaults change or are archived', function (): void {
    $admin = User::factory()->admin()->create();
    $service = CatalogService::factory()->forOrganization($admin->organization)->create(['label' => 'Driveway', 'amount' => 42500]);
    $client = Client::factory()->forOrganization($admin->organization)->create();
    $this->actingAs($admin)->post(route('contracts.store'), [
        'clientId' => $client->id, 'season' => Season::current()->label,
        'services' => [['label' => 'Custom driveway', 'amount' => 50000]],
        'payments' => collect(['deposit', 'first', 'second', 'balance'])
            ->mapWithKeys(fn (string $slot): array => [$slot => ['amount' => 12500, 'dueDate' => null]])->all(),
    ])->assertSessionHasNoErrors()->assertRedirect();
    $contract = Contract::query()->sole();
    $this->put(route('catalog-services.update', $service), ['label' => 'New default', 'amount' => 60000])->assertRedirect();
    $this->delete(route('catalog-services.destroy', $service))->assertRedirect();
    $line = $contract->refresh()->services->sole();
    expect($line->label)->toBe('Custom driveway')->and($line->amount)->toBe(50000);
    $this->get(route('contracts.edit', $contract))->assertInertia(fn (Assert $page): Assert => $page
        ->where('contract.services.0.label', 'Custom driveway')->where('contract.services.0.amount', 50000)->has('catalogServices', 0));
});

it('rejects cross organization edits and archives', function (): void {
    $admin = User::factory()->admin()->create();
    $foreign = CatalogService::factory()->create();
    $this->actingAs($admin)->put(route('catalog-services.update', $foreign), ['label' => 'Stolen', 'amount' => 0])->assertNotFound();
    $this->delete(route('catalog-services.destroy', $foreign))->assertNotFound();
    expect($foreign->refresh()->deleted_at)->toBeNull()->and($foreign->label)->not->toBe('Stolen');
    expect(fn () => app(SaveCatalogService::class)->handle($admin, ['label' => 'Stolen', 'amount' => 0], $foreign))
        ->toThrow(ModelNotFoundException::class);
});

it('denies catalog management to workers and regular staff', function (bool $worker): void {
    $user = $worker ? User::factory()->worker()->create() : User::factory()->create();
    $service = CatalogService::factory()->forOrganization($user->organization)->create();
    $this->actingAs($user)->get(route('catalog-services.index'))->assertForbidden();
    $this->post(route('catalog-services.store'), ['label' => 'Service', 'amount' => 100])->assertForbidden();
    $this->put(route('catalog-services.update', $service), ['label' => 'Changed', 'amount' => 200])->assertForbidden();
    $this->delete(route('catalog-services.destroy', $service))->assertForbidden();
    expect(fn () => app(SaveCatalogService::class)->handle($user, ['label' => 'Service', 'amount' => 100]))
        ->toThrow(AuthorizationException::class);
})->with([false, true]);

it('requires authentication and verified email for the catalog', function (): void {
    $service = CatalogService::factory()->create();
    $this->get(route('catalog-services.index'))->assertRedirect(route('login'));
    $this->post(route('catalog-services.store'))->assertRedirect(route('login'));
    $this->put(route('catalog-services.update', $service))->assertRedirect(route('login'));
    $this->delete(route('catalog-services.destroy', $service))->assertRedirect(route('login'));
    $admin = User::factory()->admin()->unverified()->create();
    $this->actingAs($admin)->get(route('catalog-services.index'))->assertRedirect(route('verification.notice'));
});

it('validates service names and integer cent prices', function (array $payload, string $field): void {
    $admin = User::factory()->admin()->create();
    $service = CatalogService::factory()->forOrganization($admin->organization)->create();
    $this->actingAs($admin)->post(route('catalog-services.store'), $payload)->assertSessionHasErrors($field);
    $this->put(route('catalog-services.update', $service), $payload)->assertSessionHasErrors($field);
    expect($service->refresh()->amount)->toBe(42500);
})->with([
    'empty label' => [['label' => ' ', 'amount' => 100], 'label'],
    'long label' => [['label' => str_repeat('x', 256), 'amount' => 100], 'label'],
    'array label' => [['label' => ['invalid'], 'amount' => 100], 'label'],
    'negative' => [['label' => 'Service', 'amount' => -1], 'amount'],
    'too large' => [['label' => 'Service', 'amount' => 10000001], 'amount'],
    'decimal cents' => [['label' => 'Service', 'amount' => 99.99], 'amount'],
    'missing amount' => [['label' => 'Service'], 'amount'],
]);

it('accepts a zero price and seeds demo defaults without overwriting edits', function (): void {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->post(route('catalog-services.store'), ['label' => 'Free inspection', 'amount' => 0])->assertSessionHasNoErrors();
    $seeder = new CatalogServiceSeeder;
    $seeder->run($admin->organization);
    $driveway = CatalogService::query()->where('label', 'Déneigement entrée')->firstOrFail();
    $driveway->update(['amount' => 12345]);
    $seeder->run($admin->organization);
    expect(CatalogService::query()->count())->toBe(4)->and($driveway->refresh()->amount)->toBe(12345);
});
