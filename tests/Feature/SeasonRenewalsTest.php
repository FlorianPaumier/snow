<?php

declare(strict_types=1);

use App\Actions\AcceptQuote;
use App\Actions\AnonymizeClient;
use App\Actions\UpdateQuote;
use App\Enums\PaymentSlot;
use App\Mail\QuoteMail;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractService;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\User;
use App\ValueObjects\Season;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
    $this->client = Client::factory()->forOrganization($this->admin->organization)->create();
    $this->sourceSeason = Season::current()->label;
    $this->targetSeason = Season::current()->next()->label;
    $this->contract = Contract::factory()->forClient($this->client)->create(['season' => $this->sourceSeason]);
    ContractService::factory()->forContract($this->contract)->create(['label' => 'Driveway', 'amount' => 40000]);
    foreach (PaymentSlot::cases() as $slot) {
        Payment::factory()->forContract($this->contract)->paid()->slot($slot)->create(['amount' => 10000, 'due_date' => '2026-12-01']);
    }

    Mail::fake();
    $this->actingAs($this->admin);
});

function renewalSelection(): array
{
    return ['sourceSeason' => test()->sourceSeason, 'targetSeason' => test()->targetSeason, 'contractIds' => [test()->contract->id]];
}

function renewalEditPayload(Quote $quote): array
{
    return ['clientId' => $quote->client_id, 'season' => $quote->season, 'services' => $quote->services,
        'payments' => $quote->payments, 'renewalReviewed' => true];
}

it('previews selected source accounts without generating or sending offers', function (): void {
    $this->get('/contracts/renewals')->assertInertia(fn (Assert $page): Assert => $page
        ->component('contract/renewals')->where('sourceSeason', $this->sourceSeason)
        ->where('targetSeason', $this->targetSeason)->has('contracts.data', 1)
        ->where('contracts.data.0.total', 40000)->where('counts.draft', 0));
    expect(Quote::query()->count())->toBe(0);
    Mail::assertNothingQueued();
});

it('prepares a linked draft once and preserves the original financial records', function (): void {
    $before = $this->contract->fresh(['services', 'payments'])->toArray();
    $this->post('/contracts/renewals', renewalSelection())->assertRedirect();
    $this->post('/contracts/renewals', renewalSelection())->assertRedirect();
    $quote = Quote::query()->sole();
    expect($quote->source_contract_id)->toBe($this->contract->id)->and($quote->season)->toBe($this->targetSeason)
        ->and($quote->status)->toBe('draft')->and($quote->renewal_reviewed_at)->toBeNull()
        ->and($quote->services)->toBe([['label' => 'Driveway', 'amount' => 40000]])
        ->and($quote->payments)->toHaveCount(4);
    foreach ($quote->payments as $payment) {
        expect($payment)->toBe(['amount' => 10000, 'dueDate' => null]);
    }

    expect($this->contract->fresh(['services', 'payments'])->toArray())->toBe($before)->and(Contract::query()->count())->toBe(1);
    Mail::assertNothingQueued();
});

it('requires explicit price and date review before a renewal can be sent', function (): void {
    $this->post('/contracts/renewals', renewalSelection());
    $quote = Quote::query()->sole();
    $this->post('/quotes/'.$quote->id.'/send')->assertSessionHasErrors('quote');
    $payload = renewalEditPayload($quote);
    unset($payload['renewalReviewed']);
    $this->put('/quotes/'.$quote->id, $payload)->assertSessionHasErrors('renewalReviewed');
    $payload['renewalReviewed'] = true;
    $payload['services'][0]['amount'] = 44000;
    $payload['payments']['balance'] = ['amount' => 14000, 'dueDate' => '2028-04-01'];
    $this->put('/quotes/'.$quote->id, $payload)->assertRedirect();
    expect($quote->refresh()->renewal_reviewed_at)->not->toBeNull();
    $this->post('/quotes/'.$quote->id.'/send')->assertRedirect();
    Mail::assertQueued(QuoteMail::class, 1);
    auth()->logout();
    $this->post($quote->refresh()->reviewUrl(), ['accepted' => true])->assertRedirect();
    $renewed = Contract::query()->where('season', $this->targetSeason)->sole();
    expect($renewed->services->first()->amount)->toBe(44000)
        ->and($renewed->payments->every(fn (Payment $payment): bool => ! $payment->paid && $payment->method === null && $payment->paid_at === null))->toBeTrue()
        ->and($renewed->payments->firstWhere('slot', PaymentSlot::SLOT_BALANCE)->due_date->toDateString())->toBe('2028-04-01');
});

it('reports existing target contracts and quotes instead of creating duplicate offers', function (): void {
    Contract::factory()->forClient($this->client)->create(['season' => $this->targetSeason]);
    $this->post('/contracts/renewals', renewalSelection())->assertRedirect()->assertSessionHas('renewalResult.existing', 1);
    expect(Quote::query()->count())->toBe(0);
    $this->get('/contracts/renewals')->assertInertia(fn (Assert $page): Assert => $page->where('contracts.data.0.blocked', true));
});

it('skips anonymized accounts and rejects mixed tenant batches atomically', function (): void {
    resolve(AnonymizeClient::class)->handle($this->client, $this->admin);
    $this->post('/contracts/renewals', renewalSelection())->assertRedirect()->assertSessionHas('renewalResult.anonymized', 1);
    expect(Quote::query()->count())->toBe(0);
    $other = Contract::factory()->create(['season' => $this->sourceSeason]);
    $payload = renewalSelection();
    $payload['contractIds'][] = $other->id;
    $this->post('/contracts/renewals', $payload)->assertNotFound();
    expect(Quote::query()->count())->toBe(0);
});

it('rejects unauthorized workers and invalid or unrelated season selections', function (): void {
    $this->get('/contracts/renewals?sourceSeason=invalid')->assertSessionHasErrors('sourceSeason');
    $payload = renewalSelection();
    $payload['targetSeason'] = $this->sourceSeason;
    $this->post('/contracts/renewals', $payload)->assertSessionHasErrors('targetSeason');
    $payload = renewalSelection();
    $payload['sourceSeason'] = Season::current()->previous()->label;
    $payload['targetSeason'] = $this->sourceSeason;
    $this->post('/contracts/renewals', $payload)->assertNotFound();
    $worker = User::factory()->worker()->forOrganization($this->admin->organization)->create();
    $this->actingAs($worker)->get('/contracts/renewals')->assertForbidden();
    $this->post('/contracts/renewals', renewalSelection())->assertForbidden();
    expect(Quote::query()->count())->toBe(0);
});

it('preserves renewal provenance and resets review when replacing an issued offer', function (): void {
    $this->post('/contracts/renewals', renewalSelection());
    $quote = Quote::query()->sole();
    $this->put('/quotes/'.$quote->id, renewalEditPayload($quote));
    $this->post('/quotes/'.$quote->id.'/send');
    $this->post('/quotes/'.$quote->id.'/replace')->assertRedirect();
    $replacement = Quote::query()->where('status', 'draft')->sole();
    expect($replacement->source_contract_id)->toBe($this->contract->id)->and($replacement->renewal_reviewed_at)->toBeNull();
    $this->post('/contracts/renewals', renewalSelection())->assertSessionHas('renewalResult.existing', 1);
    expect(Quote::query()->count())->toBe(2);
    $this->post('/quotes/'.$replacement->id.'/send')->assertSessionHasErrors('quote');
});

it('records an office reported decline and revokes an issued link', function (): void {
    $this->post('/contracts/renewals', renewalSelection());
    $quote = Quote::query()->sole();
    $this->put('/quotes/'.$quote->id, renewalEditPayload($quote));
    $this->post('/quotes/'.$quote->id.'/send');
    $url = $quote->refresh()->reviewUrl();
    $this->post('/quotes/'.$quote->id.'/decline-renewal')->assertRedirect();
    expect($quote->refresh()->status)->toBe('declined');
    $this->get('/contracts/renewals')->assertInertia(fn (Assert $page): Assert => $page->where('counts.declined', 1)->where('counts.sent', 0));
    auth()->logout();
    $this->get($url)->assertGone();
});

it('counts expired sent offers separately without changing their stored status', function (): void {
    $this->post('/contracts/renewals', renewalSelection());
    $quote = Quote::query()->sole();
    $this->put('/quotes/'.$quote->id, renewalEditPayload($quote));
    $this->post('/quotes/'.$quote->id.'/send');
    $this->travel(15)->days();
    $this->get('/contracts/renewals')->assertInertia(fn (Assert $page): Assert => $page->where('counts.expired', 1)->where('counts.sent', 0));
    expect($quote->refresh()->status)->toBe('sent');
});

it('keeps renewal client and season identity fixed while allowing price changes', function (): void {
    $this->post('/contracts/renewals', renewalSelection());
    $quote = Quote::query()->sole();
    $payload = renewalEditPayload($quote);
    $payload['clientId'] = Client::factory()->forOrganization($this->admin->organization)->create()->id;
    $this->put('/quotes/'.$quote->id, $payload)->assertSessionHasErrors('quote');
    expect($quote->refresh()->client_id)->toBe($this->client->id);
});

it('requires explicit review even when updating through the reusable action', function (): void {
    $this->post('/contracts/renewals', renewalSelection());
    $quote = Quote::query()->sole();
    $payload = renewalEditPayload($quote);
    unset($payload['renewalReviewed']);
    expect(fn () => resolve(UpdateQuote::class)->handle($this->admin, $quote, $payload))
        ->toThrow(ValidationException::class);
    expect($quote->refresh()->renewal_reviewed_at)->toBeNull();
});

it('rejects a declined renewal when acceptance is called directly', function (): void {
    $this->post('/contracts/renewals', renewalSelection());
    $quote = Quote::query()->sole();
    $this->put('/quotes/'.$quote->id, renewalEditPayload($quote));
    $this->post('/quotes/'.$quote->id.'/send');
    $this->post('/quotes/'.$quote->id.'/decline-renewal');
    expect(fn () => resolve(AcceptQuote::class)->handle($quote->refresh()))
        ->toThrow(HttpException::class);
    expect(Contract::query()->where('season', $this->targetSeason)->count())->toBe(0);
});
