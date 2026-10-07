<?php

declare(strict_types=1);

use App\Actions\AnonymizeClient;
use App\Actions\SaveTourOrder;
use App\Models\Client;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->withoutVite();
    $this->admin = User::factory()->admin()->create();
    $this->worker = User::factory()->worker()->forOrganization($this->admin->organization)->create();
    $this->clients = Client::factory()->forOrganization($this->admin->organization)->count(2)->create();
    $this->tour = Tour::factory()->create([
        'organization_id' => $this->admin->organization_id,
        'worker_id' => $this->worker->id,
        'stops' => $this->clients->map(fn (Client $client): array => [
            'id' => $client->id, 'name' => $client->displayName(), 'address' => '100 rue Saint-Paul, Montréal, Canada', 'remark' => 'Entrée arrière',
        ])->all(),
    ]);
});

it('creates a tour with assigned clients and identity snapshots', function (): void {
    $this->actingAs($this->admin)->post(route('tours.store'), [
        'name' => 'Tempête du matin', 'worker_id' => $this->worker->id,
        'scheduled_date' => today()->toDateString(), 'client_ids' => $this->clients->pluck('id')->reverse()->values()->all(),
    ])->assertRedirect();

    $tour = Tour::query()->where('name', 'Tempête du matin')->firstOrFail();
    expect($tour->stops[0]['id'])->toBe($this->clients[1]->id)
        ->and($tour->organization_id)->toBe($this->admin->organization_id);
    $original = $tour->stops;
    $this->clients[1]->update(['firstName' => 'Changed']);
    expect($tour->refresh()->stops)->toBe($original);
});

it('renders the admin tour screens', function (): void {
    $this->actingAs($this->admin)->get(route('tours.index'))->assertOk()->assertInertia(fn ($page) => $page->component('tours/index')->has('tours.data', 1));
    $this->get(route('tours.create'))->assertOk()->assertInertia(fn ($page) => $page->component('tours/create')->has('workers', 1)->has('clients', 2));
    $this->get(route('tours.show', $this->tour))->assertOk()->assertInertia(fn ($page) => $page->component('tours/show')->has('tour.stops', 2));
});

it('rejects workers or clients from other organizations', function (): void {
    $foreignWorker = User::factory()->worker()->create();
    $foreignClient = Client::factory()->create();
    $this->actingAs($this->admin)->post(route('tours.store'), [
        'name' => 'Invalid', 'worker_id' => $foreignWorker->id,
        'scheduled_date' => today()->toDateString(), 'client_ids' => [$foreignClient->id],
    ])->assertSessionHasErrors(['worker_id', 'client_ids.0']);
});

it('rejects non-worker assignment and anonymized clients', function (): void {
    $this->clients[0]->forceFill(['anonymized_at' => now()])->save();
    $this->actingAs($this->admin)->post(route('tours.store'), [
        'name' => 'Invalid', 'worker_id' => $this->admin->id,
        'scheduled_date' => today()->toDateString(), 'client_ids' => [$this->clients[0]->id],
    ])->assertSessionHasErrors(['worker_id', 'client_ids.0']);
});

it('redirects workers to their dashboard and shows only their tours', function (): void {
    Tour::factory()->create(['organization_id' => $this->admin->organization_id]);
    $this->actingAs($this->worker)->get(route('dashboard'))->assertRedirect(route('tours.worker'));
    $this->get(route('tours.worker'))->assertOk()->assertInertia(fn ($page) => $page->component('tours/worker')->has('tours', 1)->where('tours.0.id', $this->tour->id));
});

it('starts and finishes a tour with idempotent timestamps and records history', function (): void {
    $this->actingAs($this->worker)->post(route('tours.start', $this->tour))->assertRedirect(route('tours.worker'));
    $started = $this->tour->refresh()->started_at;
    $this->travel(10)->minutes();
    $this->post(route('tours.start', $this->tour))->assertSessionHasNoErrors();
    expect($this->tour->refresh()->started_at->equalTo($started))->toBeTrue();
    $this->post(route('tours.finish', $this->tour))->assertSessionHasNoErrors();
    $finished = $this->tour->refresh()->finished_at;
    expect((int) $started->diffInMinutes($finished))->toBe(10);
    $this->travel(5)->minutes();
    $this->post(route('tours.finish', $this->tour))->assertSessionHasNoErrors();
    expect($this->tour->refresh()->finished_at->equalTo($finished))->toBeTrue();
    $this->get(route('tours.worker'))->assertInertia(fn ($page) => $page->has('tours', 0)->has('history.data', 1)->where('history.data.0.id', $this->tour->id));
    $this->post(route('tours.start', $this->tour))->assertSessionHasErrors('tour');
});

it('prevents finishing an unstarted tour and starting future or concurrent tours', function (): void {
    $this->actingAs($this->worker)->post(route('tours.finish', $this->tour))->assertSessionHasErrors('tour');
    $this->tour->update(['scheduled_date' => today()->addDay()]);
    $this->post(route('tours.start', $this->tour))->assertSessionHasErrors('tour');
    $this->tour->update(['scheduled_date' => today()]);
    Tour::factory()->create(['organization_id' => $this->admin->organization_id, 'worker_id' => $this->worker->id, 'started_at' => now()]);
    $this->post(route('tours.start', $this->tour))->assertSessionHasErrors('tour');
    expect($this->tour->refresh()->started_at)->toBeNull();
});

it('keeps an overnight tour available to finish the next day', function (): void {
    $this->tour->update(['scheduled_date' => today()->subDay(), 'started_at' => now()->subHours(3)]);
    $this->actingAs($this->worker)->get(route('tours.worker'))->assertInertia(fn ($page) => $page->has('tours', 1));
    $this->post(route('tours.finish', $this->tour))->assertSessionHasNoErrors();
    expect($this->tour->refresh()->finished_at)->not->toBeNull();
});

it('denies access to another workers tour and admin operations', function (): void {
    $other = User::factory()->worker()->forOrganization($this->admin->organization)->create();
    $this->actingAs($other)->get(route('tours.show', $this->tour))->assertForbidden();
    $this->post(route('tours.start', $this->tour))->assertForbidden();
    $this->post(route('tours.finish', $this->tour))->assertForbidden();
    $this->get(route('tours.index'))->assertForbidden();
    $this->get(route('tours.create'))->assertForbidden();
    $this->post(route('tours.store'), [])->assertForbidden();
    $this->post(route('tours.optimize', $this->tour))->assertForbidden();
    $this->put(route('tours.update', $this->tour), ['client_ids' => $this->clients->pluck('id')->all()])->assertForbidden();
    Http::assertNothingSent();
});

it('hides foreign organization tours and denies regular users', function (): void {
    $foreign = User::factory()->admin()->create();
    $this->actingAs($foreign)->get(route('tours.show', $this->tour))->assertNotFound();
    $this->post(route('tours.optimize', $this->tour))->assertNotFound();
    $this->get(route('tours.index'))->assertInertia(fn ($page) => $page->has('tours.data', 0));
    $regular = User::factory()->forOrganization($this->admin->organization)->create();
    $this->actingAs($regular)->get(route('tours.worker'))->assertForbidden();
    $this->get(route('tours.show', $this->tour))->assertForbidden();
});

it('allows manual reorder while preserving the assigned clients', function (): void {
    $ids = $this->clients->pluck('id')->reverse()->values()->all();
    $this->actingAs($this->admin)->put(route('tours.update', $this->tour), ['client_ids' => $ids])->assertSessionHasNoErrors();
    expect(array_column($this->tour->refresh()->stops, 'id'))->toBe($ids);
    $this->put(route('tours.update', $this->tour), ['client_ids' => [$ids[0]]])->assertSessionHasErrors('client_ids');
    $this->tour->update(['started_at' => now()]);
    $this->put(route('tours.update', $this->tour), ['client_ids' => $ids])->assertSessionHasErrors('tour');
});

it('optimizes and persists an order using cached address geocoding', function (): void {
    config(['services.openrouteservice.key' => 'test-key']);
    Http::fake([
        '*/geocode/search*' => Http::response(['features' => [['geometry' => ['coordinates' => [-73.57, 45.50]], 'properties' => ['confidence' => 1]]]]),
        '*/optimization' => Http::response(['code' => 0, 'unassigned' => [], 'routes' => [['vehicle' => 1, 'steps' => [['type' => 'job', 'id' => 2], ['type' => 'job', 'id' => 1]]]]]),
    ]);
    $this->actingAs($this->admin)->post(route('tours.optimize', $this->tour))->assertSessionHasNoErrors();
    expect(array_column($this->tour->refresh()->stops, 'id'))->toBe($this->clients->pluck('id')->reverse()->values()->all())
        ->and($this->tour->optimized_at)->not->toBeNull();
    Http::assertSentCount(3);
    $this->post(route('tours.optimize', $this->tour))->assertSessionHasNoErrors();
    Http::assertSentCount(4);
});

it('preserves the manual order when optimization fails', function (): void {
    config(['services.openrouteservice.key' => 'test-key']);
    Http::fake(['*/geocode/search*' => Http::response(['features' => []])]);
    $original = $this->tour->stops;
    $this->actingAs($this->admin)->post(route('tours.optimize', $this->tour))->assertSessionHasErrors('optimization');
    expect($this->tour->refresh()->stops)->toBe($original)->and($this->tour->optimized_at)->toBeNull();
});

it('rejects an optimization applied to a tour changed in the meantime', function (): void {
    $original = $this->tour->stops;
    $this->actingAs($this->admin);
    $this->tour->update(['stops' => array_reverse($original)]);
    resolve(SaveTourOrder::class)->handle($this->admin, $this->tour, array_column($original, 'id'), true, $original);
})->throws(ValidationException::class);

it('erases client identities from tour snapshots during anonymization', function (): void {
    $this->actingAs($this->admin);
    resolve(AnonymizeClient::class)->handle($this->clients[0], $this->admin);
    $stops = $this->tour->refresh()->stops;
    expect($stops[0]['name'])->toBe(__('clients.anonymized_name'))
        ->and($stops[0]['address'])->toBe('')
        ->and($stops[0]['remark'])->toBeNull()
        ->and($stops[1]['name'])->toBe($this->clients[1]->displayName());
});

it('preserves tour history after a worker is deleted', function (): void {
    $this->worker->delete();
    expect($this->tour->refresh()->worker_id)->toBeNull()->and($this->tour->stops)->toHaveCount(2);
});

it('requires a key and departure address without modifying the tour', function (): void {
    config(['services.openrouteservice.key' => '']);
    $this->actingAs($this->admin)->post(route('tours.optimize', $this->tour))->assertSessionHasErrors('optimization');
    config(['services.openrouteservice.key' => 'test-key']);
    $this->tour->update(['departure_address' => null]);
    $this->post(route('tours.optimize', $this->tour))->assertSessionHasErrors('optimization');
    Http::assertNothingSent();
});

it('keeps the original order when the optimizer omits a client', function (): void {
    config(['services.openrouteservice.key' => 'test-key']);
    Http::fake([
        '*/geocode/search*' => Http::response(['features' => [['geometry' => ['coordinates' => [-73.57, 45.50]], 'properties' => ['confidence' => 1]]]]),
        '*/optimization' => Http::response(['code' => 0, 'unassigned' => [], 'routes' => [['vehicle' => 1, 'steps' => [['type' => 'job', 'id' => 2]]]]]),
    ]);
    $original = $this->tour->stops;
    $this->actingAs($this->admin)->post(route('tours.optimize', $this->tour))->assertSessionHasErrors('optimization');
    expect($this->tour->refresh()->stops)->toBe($original);
});
