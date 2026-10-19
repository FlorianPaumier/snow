<?php

declare(strict_types=1);

use App\Actions\AnonymizeClient;
use App\Models\Client;
use App\Models\Tour;
use App\Models\TourDispatch;
use App\Models\TourTemplate;
use App\Models\User;
use App\ValueObjects\Address;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
    $this->worker = User::factory()->worker()->forOrganization($this->admin->organization)->create();
    $this->clients = Client::factory()->count(2)->forOrganization($this->admin->organization)->create();
    $this->actingAs($this->admin);
});

function templatePayload(): array
{
    return ['name' => 'North route', 'worker_id' => test()->worker->id, 'departure_address' => 'Depot',
        'arrival_address' => 'Garage', 'client_ids' => test()->clients->pluck('id')->reverse()->values()->all()];
}

function dispatchPayload(array $ids): array
{
    return ['request_key' => (string) Str::uuid(), 'scheduled_date' => today()->toDateString(), 'template_ids' => $ids];
}

it('creates an editable template without creating a tour', function (): void {
    $this->post('/tour-templates', templatePayload())->assertRedirect();
    $template = TourTemplate::query()->sole();
    expect($template->client_ids)->toBe(templatePayload()['client_ids'])->and(Tour::query()->count())->toBe(0);
    $payload = templatePayload();
    $payload['name'] = 'Revised north route';
    $payload['client_ids'] = array_reverse($payload['client_ids']);
    $this->put('/tour-templates/'.$template->id, $payload)->assertRedirect();
    expect($template->refresh()->name)->toBe('Revised north route')->and($template->client_ids)->toBe($payload['client_ids']);
});

it('dispatches a batch once and snapshots the current client details in template order', function (): void {
    $this->post('/tour-templates', templatePayload());
    $template = TourTemplate::query()->sole();
    $client = $this->clients->last();
    $client->update(['address' => new Address('Updated street', 'Montréal', 'H1H 1H1', 'Canada'), 'remark' => 'New access note']);

    $payload = dispatchPayload([$template->id]);
    $this->post('/tour-dispatches', $payload)->assertRedirect();
    $tour = Tour::query()->sole();
    $snapshot = $tour->stops;
    expect($snapshot[0]['id'])->toBe($client->id)->and($snapshot[0]['address'])->toContain('Updated street')
        ->and($snapshot[0]['remark'])->toBe('New access note')->and($tour->worker_id)->toBe($this->worker->id)
        ->and($tour->departure_address)->toBe('Depot')->and($tour->started_at)->toBeNull();
    $this->post('/tour-dispatches', $payload)->assertRedirect();
    expect(Tour::query()->count())->toBe(1)->and(TourDispatch::query()->count())->toBe(1);
    $this->put('/tour-templates/'.$template->id, [...templatePayload(), 'name' => 'Changed template']);
    $client->update(['remark' => 'Changed again']);
    $this->post('/tour-dispatches', dispatchPayload([$template->id]))->assertRedirect();
    expect(Tour::query()->count())->toBe(2)->and($tour->refresh()->stops)->toBe($snapshot)
        ->and($tour->name)->toBe('North route');
});

it('rejects reuse of a batch key with different input', function (): void {
    $this->post('/tour-templates', templatePayload());
    $payload = dispatchPayload([TourTemplate::query()->sole()->id]);
    $this->post('/tour-dispatches', $payload)->assertRedirect();
    $payload['scheduled_date'] = today()->addDay()->toDateString();
    $this->post('/tour-dispatches', $payload)->assertSessionHasErrors('request_key');
    expect(Tour::query()->count())->toBe(1);
});

it('previews missing clients and rolls back the entire mixed batch', function (): void {
    $this->post('/tour-templates', templatePayload());
    $valid = TourTemplate::query()->sole();
    $this->post('/tour-templates', [...templatePayload(), 'name' => 'Invalid route']);
    $invalid = TourTemplate::query()->whereKeyNot($valid->id)->sole();
    $invalid->update(['client_ids' => [(string) Str::uuid()]]);
    $this->get('/tour-templates')->assertInertia(fn (Assert $page): Assert => $page->component('tour-templates/index')
        ->has('templates.data', 2)->where('templates.data.0.valid', true)->where('templates.data.1.valid', false));
    $this->post('/tour-dispatches', dispatchPayload([$valid->id, $invalid->id]))->assertSessionHasErrors('template_ids');
    expect(Tour::query()->count())->toBe(0)->and(TourDispatch::query()->count())->toBe(0);
});

it('reports deleted workers and anonymized clients without dispatching', function (): void {
    $this->post('/tour-templates', templatePayload());
    $template = TourTemplate::query()->sole();
    $this->worker->delete();
    $this->get('/tour-templates')->assertInertia(fn (Assert $page): Assert => $page->where('templates.data.0.valid', false));
    $this->post('/tour-dispatches', dispatchPayload([$template->id]))->assertSessionHasErrors('template_ids');
    $worker = User::factory()->worker()->forOrganization($this->admin->organization)->create();
    $template->update(['worker_id' => $worker->id]);
    resolve(AnonymizeClient::class)->handle($this->clients->first(), $this->admin);
    $this->get('/tour-templates')->assertInertia(fn (Assert $page): Assert => $page->where('templates.data.0.valid', false));
    $this->post('/tour-dispatches', dispatchPayload([$template->id]))->assertSessionHasErrors('template_ids');
    expect(Tour::query()->count())->toBe(0);
});

it('isolates template management and dispatch by organization and role', function (): void {
    $this->post('/tour-templates', templatePayload());
    $template = TourTemplate::query()->sole();
    $other = User::factory()->admin()->create();
    $this->actingAs($other)->get('/tour-templates/'.$template->id.'/edit')->assertNotFound();
    $this->put('/tour-templates/'.$template->id, templatePayload())->assertNotFound();
    $this->delete('/tour-templates/'.$template->id)->assertNotFound();
    $this->post('/tour-dispatches', dispatchPayload([$template->id]))->assertNotFound();
    $this->actingAs($this->worker)->get('/tour-templates')->assertForbidden();
    $this->post('/tour-templates', templatePayload())->assertForbidden();
    $this->post('/tour-dispatches', dispatchPayload([$template->id]))->assertForbidden();
    expect(Tour::query()->count())->toBe(0);
});

it('rejects invalid template inputs and dispatch dates', function (): void {
    $payload = templatePayload();
    $payload['client_ids'][] = $payload['client_ids'][0];
    $this->post('/tour-templates', $payload)->assertSessionHasErrors('client_ids.0');
    $this->post('/tour-templates', [...templatePayload(), 'worker_id' => $this->admin->id])->assertSessionHasErrors('worker_id');
    $foreignClient = Client::factory()->create();
    $this->post('/tour-templates', [...templatePayload(), 'client_ids' => [$foreignClient->id]])->assertSessionHasErrors('client_ids.0');
    $this->post('/tour-templates', templatePayload());
    $request = dispatchPayload([TourTemplate::query()->sole()->id]);
    $request['scheduled_date'] = today()->subDay()->toDateString();
    $this->post('/tour-dispatches', $request)->assertSessionHasErrors('scheduled_date');
    expect(Tour::query()->count())->toBe(0);
});

it('deletes a template without changing already dispatched tours', function (): void {
    $this->post('/tour-templates', templatePayload());
    $template = TourTemplate::query()->sole();
    $payload = dispatchPayload([$template->id]);
    $this->post('/tour-dispatches', $payload);
    $tour = Tour::query()->sole();
    $snapshot = $tour->stops;
    $this->delete('/tour-templates/'.$template->id)->assertRedirect();
    expect(TourTemplate::query()->count())->toBe(0)->and($tour->refresh()->stops)->toBe($snapshot);
    $this->post('/tour-dispatches', $payload)->assertRedirect();
    expect(Tour::query()->count())->toBe(1);
});

it('shows route level office counts and keeps worker assignments unchanged', function (): void {
    $this->post('/tour-templates', templatePayload());
    $this->post('/tour-dispatches', dispatchPayload([TourTemplate::query()->sole()->id]));
    $tour = Tour::query()->sole();
    $this->get('/tours')->assertInertia(fn (Assert $page): Assert => $page->where('counts.planned', 1)->where('counts.assigned', 1)->where('counts.active', 0));
    $this->actingAs($this->worker)->get('/my-tours')->assertOk();
    $this->post('/tours/'.$tour->id.'/start')->assertRedirect();
    $this->actingAs($this->admin)->get('/tours')->assertInertia(fn (Assert $page): Assert => $page->where('counts.active', 1));
    $other = User::factory()->worker()->forOrganization($this->admin->organization)->create();
    $this->actingAs($other)->get('/tours/'.$tour->id)->assertForbidden();
});

it('dispatches multiple templates and treats a reordered retry as the same batch', function (): void {
    $this->post('/tour-templates', templatePayload());
    $this->post('/tour-templates', [...templatePayload(), 'name' => 'South route']);
    $ids = TourTemplate::query()->pluck('id')->all();
    $payload = dispatchPayload($ids);
    $this->post('/tour-dispatches', $payload)->assertRedirect();
    $this->post('/tour-dispatches', [...$payload, 'template_ids' => array_reverse($ids)])->assertRedirect();
    expect(TourDispatch::query()->count())->toBe(1)->and(Tour::query()->count())->toBe(2)
        ->and(Tour::query()->pluck('dispatch_id')->unique()->count())->toBe(1);
});

it('filters office totals by date and derives finished state from tour timestamps', function (): void {
    $this->post('/tour-templates', templatePayload());
    $template = TourTemplate::query()->sole();
    $this->post('/tour-dispatches', dispatchPayload([$template->id]));
    $tour = Tour::query()->sole();
    $next = dispatchPayload([$template->id]);
    $next['scheduled_date'] = today()->addDay()->toDateString();
    $this->post('/tour-dispatches', $next);
    $this->actingAs($this->worker)->post('/tours/'.$tour->id.'/start')->assertRedirect();
    $this->post('/tours/'.$tour->id.'/finish')->assertRedirect();
    $this->actingAs($this->admin)->get('/tours?date='.today()->toDateString())->assertInertia(fn (Assert $page): Assert => $page
        ->has('tours.data', 1)->where('counts.finished', 1)->where('counts.planned', 0)->where('counts.active', 0));
});

it('renders template creation and editing with current tenant options', function (): void {
    User::factory()->worker()->create();
    $this->get('/tour-templates/create')->assertInertia(fn (Assert $page): Assert => $page
        ->component('tours/create')->where('templateMode', true)->has('workers', 1)->has('clients', 2)
        ->where('workers.0.id', $this->worker->id));
    $this->post('/tour-templates', templatePayload());
    $template = TourTemplate::query()->sole();
    $this->get('/tour-templates/'.$template->id.'/edit')->assertInertia(fn (Assert $page): Assert => $page
        ->component('tours/create')->where('templateMode', true)->where('template.id', $template->id)
        ->where('template.client_ids', templatePayload()['client_ids'])->has('workers', 1));
});
