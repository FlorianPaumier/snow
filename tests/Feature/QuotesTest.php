<?php

declare(strict_types=1);

use App\Actions\AnonymizeClient;
use App\Mail\QuoteMail;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Quote;
use App\Models\User;
use App\ValueObjects\Season;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

function quotePayload(Client $client): array
{
    return ['clientId' => $client->id, 'season' => Season::current()->label,
        'services' => [['label' => 'Snow removal', 'amount' => 40000]],
        'payments' => collect(['deposit', 'first', 'second', 'balance'])->mapWithKeys(fn ($slot): array => [$slot => ['amount' => 10000, 'dueDate' => null]])->all()];
}

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
    $this->client = Client::factory()->forOrganization($this->admin->organization)->create(['email' => 'homeowner@example.com']);
    Mail::fake();
});

it('creates a draft without creating revenue and displays its form and list', function (): void {
    $this->actingAs($this->admin)->get('/quotes/create')->assertOk();
    $this->post('/quotes', quotePayload($this->client))->assertRedirect();
    expect(Quote::query()->count())->toBe(1)->and(Contract::query()->count())->toBe(0);
    $quote = Quote::query()->firstOrFail();
    expect($quote->status)->toBe('draft');
    $this->get('/quotes')->assertInertia(fn (Assert $page): Assert => $page->component('quotes/index')->has('quotes.data', 1));
    $this->get('/quotes/'.$quote->id)->assertOk();
});

it('sends a frozen offer and guest acceptance creates exactly one unpaid contract', function (): void {
    $this->actingAs($this->admin)->post('/quotes', quotePayload($this->client));
    $quote = Quote::query()->firstOrFail();
    $this->post('/quotes/'.$quote->id.'/send')->assertRedirect();
    $this->post('/quotes/'.$quote->id.'/send')->assertRedirect();
    Mail::assertQueued(QuoteMail::class, 1);
    Mail::assertQueued(QuoteMail::class, fn ($mail): bool => $mail->hasTo('homeowner@example.com') && str_contains((string) $mail->render(), (string) $this->admin->organization->name));
    $quote->refresh();
    expect($quote->status)->toBe('sent');
    $this->client->update(['firstName' => 'Changed']);
    auth()->logout();
    $url = $quote->reviewUrl();
    $this->get($url)->assertInertia(fn (Assert $page): Assert => $page->component('quotes/review')->where('quote.services.0.amount', 40000));
    expect(Contract::query()->count())->toBe(0);
    $this->post($url, ['accepted' => true])->assertRedirect();
    $this->post($url, ['accepted' => true])->assertRedirect();
    $contract = Contract::query()->firstOrFail();
    expect(Contract::query()->count())->toBe(1)->and($contract->services->first()->amount)->toBe(40000)
        ->and($contract->payments)->toHaveCount(4)->and($contract->payments->every(fn ($payment): bool => ! $payment->paid))->toBeTrue()
        ->and($quote->refresh()->contract_id)->toBe($contract->id)->and($quote->accepted_at)->not->toBeNull();
});

it('rejects invalid amounts and cross tenant draft data', function (): void {
    $other = Client::factory()->create();
    $this->actingAs($this->admin)->post('/quotes', quotePayload($other))->assertNotFound();
    $payload = quotePayload($this->client);
    $payload['payments']['deposit']['amount'] = 2;
    $this->post('/quotes', $payload)->assertSessionHasErrors('payments');
    $payload['services'] = ['bad'];
    $this->post('/quotes', $payload)->assertSessionHasErrors();
    expect(Quote::query()->count())->toBe(0);
});

it('requires administrator access and hides other tenant offers', function (): void {
    $this->actingAs($this->admin)->post('/quotes', quotePayload($this->client));
    $quote = Quote::query()->firstOrFail();
    $worker = User::factory()->worker()->forOrganization($this->admin->organization)->create();
    $this->actingAs($worker)->get('/quotes')->assertForbidden();
    $this->post('/quotes', quotePayload($this->client))->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->get('/quotes/'.$quote->id)->assertNotFound();
    $this->post('/quotes/'.$quote->id.'/send')->assertNotFound();
});

it('requires valid unexpired links and explicit acceptance', function (): void {
    $this->actingAs($this->admin)->post('/quotes', quotePayload($this->client));
    $quote = Quote::query()->firstOrFail();
    $this->post('/quotes/'.$quote->id.'/send');
    auth()->logout();
    $quote->refresh();
    $url = $quote->reviewUrl();
    $this->post($url, [])->assertSessionHasErrors('accepted');
    $this->get($url.'&tampered=1')->assertForbidden();
    $this->travel(15)->days();
    $this->get($url)->assertForbidden();
    expect(Contract::query()->count())->toBe(0);
});

it('replaces an issued offer without changing its snapshot and revokes the old link', function (): void {
    $this->actingAs($this->admin)->post('/quotes', quotePayload($this->client));
    $quote = Quote::query()->firstOrFail();
    $this->post('/quotes/'.$quote->id.'/send');
    $oldUrl = $quote->refresh()->reviewUrl();
    $this->post('/quotes/'.$quote->id.'/replace')->assertRedirect();
    expect($quote->refresh()->status)->toBe('replaced')->and(Quote::query()->where('status', 'draft')->count())->toBe(1);
    auth()->logout();
    $this->get($oldUrl)->assertGone();
});

it('does not send to missing email and permits retry after correction', function (): void {
    $this->client->update(['email' => null]);
    $this->actingAs($this->admin)->post('/quotes', quotePayload($this->client));
    $quote = Quote::query()->firstOrFail();
    $this->post('/quotes/'.$quote->id.'/send')->assertSessionHasErrors('quote');
    expect($quote->refresh()->status)->toBe('draft');
    Mail::assertNothingQueued();
    $this->client->update(['email' => 'corrected@example.com']);
    $this->post('/quotes/'.$quote->id.'/send')->assertRedirect();
    Mail::assertQueued(QuoteMail::class, fn ($mail) => $mail->hasTo('corrected@example.com'));
});

it('handles conflicting contracts without creating a second contract', function (): void {
    $this->actingAs($this->admin)->post('/quotes', quotePayload($this->client));
    $quote = Quote::query()->firstOrFail();
    $this->post('/quotes/'.$quote->id.'/send');
    Contract::factory()->create(['organization_id' => $this->admin->organization_id, 'client_id' => $this->client->id, 'season' => $quote->season]);
    auth()->logout();
    $this->post($quote->refresh()->reviewUrl(), ['accepted' => true])->assertSessionHasErrors('quote');
    expect(Contract::query()->count())->toBe(1)->and($quote->refresh()->status)->toBe('sent');
});

it('revokes offers and removes customer snapshots on anonymization', function (): void {
    $this->actingAs($this->admin)->post('/quotes', quotePayload($this->client));
    $quote = Quote::query()->firstOrFail();
    $this->post('/quotes/'.$quote->id.'/send');
    $url = $quote->refresh()->reviewUrl();
    resolve(AnonymizeClient::class)->handle($this->client, $this->admin);
    expect(Quote::query()->count())->toBe(0);
    auth()->logout();
    $this->get($url)->assertNotFound();
});

it('edits drafts but rejects changes to sent offer amounts', function (): void {
    $payload = quotePayload($this->client);
    $this->actingAs($this->admin)->post('/quotes', $payload);
    $quote = Quote::query()->firstOrFail();
    $payload['services'][0]['label'] = 'Updated service';
    $this->put('/quotes/'.$quote->id, $payload)->assertRedirect();
    expect($quote->refresh()->services[0]['label'])->toBe('Updated service');
    $this->post('/quotes/'.$quote->id.'/send');
    $payload['services'][0]['label'] = 'Changed after sending';
    $this->put('/quotes/'.$quote->id, $payload)->assertSessionHasErrors('quote');
    expect($quote->refresh()->services[0]['label'])->toBe('Updated service');
});

it('allows a valid capability link while signed in to an unrelated organization', function (): void {
    $this->actingAs($this->admin)->post('/quotes', quotePayload($this->client));
    $quote = Quote::query()->firstOrFail();
    $this->post('/quotes/'.$quote->id.'/send');
    $url = $quote->refresh()->reviewUrl();
    $other = User::factory()->admin()->create();
    $this->actingAs($other)->get($url)->assertOk();
    $this->post($url, ['accepted' => true])->assertRedirect();
    expect(Contract::query()->withoutGlobalScopes()->where('organization_id', $this->admin->organization_id)->count())->toBe(1)
        ->and(Contract::query()->count())->toBe(0);
});

it('does not send quotes for a season that already has a contract', function (): void {
    $this->actingAs($this->admin)->post('/quotes', quotePayload($this->client));
    $quote = Quote::query()->firstOrFail();
    Contract::factory()->create(['organization_id' => $this->admin->organization_id, 'client_id' => $this->client->id, 'season' => $quote->season]);
    $this->post('/quotes/'.$quote->id.'/send')->assertSessionHasErrors('quote');
    Mail::assertNothingQueued();
});

it('normalizes validated numeric strings before storing quote money', function (): void {
    $payload = quotePayload($this->client);
    $payload['services'][0]['amount'] = '40000';
    foreach ($payload['payments'] as &$payment) {
        $payment['amount'] = '10000';
    }

    unset($payment);
    $this->actingAs($this->admin)->post('/quotes', $payload)->assertRedirect();
    $quote = Quote::query()->firstOrFail();
    expect($quote->services[0]['amount'])->toBe(40000)->and($quote->payments['deposit']['amount'])->toBe(10000);
    $this->get('/quotes/'.$quote->id)->assertInertia(fn (Assert $page): Assert => $page->where('quote.customer.name', $this->client->displayName()));
});
