<?php

declare(strict_types=1);

use App\Actions\DeliverContractEmail;
use App\Actions\QueueContractEmails;
use App\Jobs\SendContractEmail;
use App\Mail\ContractDocumentMail;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Client\RequestException;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

/** @return array{Contract, SendContractEmail} */
function queuedContractEmail(User $admin): array
{
    $client = Client::factory()->forOrganization($admin->organization)->create(['email' => 'client@example.com']);
    $contract = Contract::factory()->forClient($client)->create();
    $contract->services()->create(['label' => 'Driveway', 'amount' => 42500, 'position' => 1]);
    Queue::fake();
    auth()->login($admin);
    app(QueueContractEmails::class)->handle($admin, [$contract->id]);
    $job = Queue::pushed(SendContractEmail::class)->first();

    return [$contract, $job];
}

it('queues single and batch emails with sender context and deduplicates pending requests', function (): void {
    $admin = User::factory()->admin()->create();
    [$first, $job] = queuedContractEmail($admin);
    $second = Contract::factory()->forClient($first->client)->create(['season' => '2027-2028']);
    $this->actingAs($admin)->fromRoute('contracts.index')->post(route('contracts.send-email'), ['contract_ids' => [$first->id, $second->id]])
        ->assertRedirect(route('contracts.index'))->assertSessionHas('status');
    Queue::assertPushed(SendContractEmail::class, 2);
    expect($job->senderId)->toBe($admin->id)->and($job->organizationId)->toBe($admin->organization_id)
        ->and($job->clientId)->toBe($first->client_id)->and($job->uniqueId())->toContain($job->requestId)
        ->and($first->refresh()->document_mail_status)->toBe('queued')->and($first->document_sent_at)->toBeNull();
    $this->post(route('contracts.send-email'), ['contract_ids' => [$first->id]])->assertRedirect();
    Queue::assertPushed(SendContractEmail::class, 2);
});

it('rejects foreign contracts and missing recipients without partially queuing a batch', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create(['email' => 'valid@example.com']);
    $valid = Contract::factory()->forClient($client)->create();
    $missing = Contract::factory()->forClient(Client::factory()->forOrganization($admin->organization)->create(['email' => null]))->create();
    $foreign = Contract::factory()->create();
    Queue::fake();
    $this->actingAs($admin)->post(route('contracts.send-email'), ['contract_ids' => [$valid->id, $missing->id]])->assertSessionHasErrors('contracts');
    $this->post(route('contracts.send-email'), ['contract_ids' => [$valid->id, $foreign->id]])->assertNotFound();
    Queue::assertNothingPushed();
    expect($valid->refresh()->document_mail_status)->toBeNull();
});

it('recovers a rejected queue submission without stranding the rest of the batch', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create(['email' => 'client@example.com']);
    $contracts = collect(['2026-2027', '2027-2028'])->map(fn (string $season): Contract => Contract::factory()->forClient($client)->create(['season' => $season]));
    $attempts = [];
    Exceptions::fake();
    $bus = Mockery::mock(Dispatcher::class);
    $bus->shouldReceive('dispatch')->times(3)->andReturnUsing(function (SendContractEmail $job) use (&$attempts): void {
        $attempts[] = $job;
        if (count($attempts) === 1) {
            throw new RuntimeException('Queue unavailable');
        }
    });
    app()->instance(Dispatcher::class, $bus);
    $this->actingAs($admin);

    app(QueueContractEmails::class)->handle($admin, $contracts->pluck('id')->all());
    expect($attempts)->toHaveCount(2);
    $failed = Contract::query()->findOrFail($attempts[0]->contractId);
    expect($failed->document_mail_status)->toBe('failed')
        ->and(Contract::query()->findOrFail($attempts[1]->contractId)->document_mail_status)->toBe('queued');
    Exceptions::assertReported(RuntimeException::class);

    app(QueueContractEmails::class)->handle($admin, [$failed->id]);
    expect($attempts)->toHaveCount(3)
        ->and($attempts[2]->requestId)->not->toBe($attempts[0]->requestId)
        ->and($failed->refresh()->document_mail_status)->toBe('queued');
    $attempts[0]->failed(null);
    expect($failed->refresh()->document_mail_status)->toBe('queued');
});

it('requires admin authorization and validates selection', function (): void {
    $this->post(route('contracts.send-email'), [])->assertRedirect(route('login'));
    $user = User::factory()->worker()->create();
    Queue::fake();
    $this->actingAs($user)->post(route('contracts.send-email'), ['contract_ids' => []])->assertForbidden();
    expect(fn () => app(QueueContractEmails::class)->handle($user, []))->toThrow(AuthorizationException::class);
    $admin = User::factory()->admin()->create();
    $id = (string) str()->uuid();
    foreach ([[], ['not-a-uuid'], [$id, $id], array_fill(0, 101, $id)] as $ids) {
        $this->actingAs($admin)->post(route('contracts.send-email'), ['contract_ids' => $ids])->assertSessionHasErrors();
    }
    Queue::assertNothingPushed();
});

it('generates and attaches the PDF, records successful sender and time, and never sends twice for a request', function (string $locale): void {
    app()->setLocale($locale);
    $admin = User::factory()->admin()->create();
    [$contract, $job] = queuedContractEmail($admin);
    Storage::fake(config()->string('filesystems.default'));
    Http::fake(['*/forms/chromium/convert/html' => Http::response('%PDF-1.4 contract', 200)]);
    Mail::fake();
    app()->setLocale('en');
    $job->handle(app(DeliverContractEmail::class));
    $job->handle(app(DeliverContractEmail::class));
    expect(app()->getLocale())->toBe('en');
    Http::assertSent(fn ($request): bool => str_contains($request->body(), '<html lang="'.$locale.'">'));
    Mail::assertSent(ContractDocumentMail::class, function (ContractDocumentMail $mail) use ($contract): bool {
        $mail->assertHasAttachment(Attachment::fromStorageDisk(config()->string('filesystems.default'), $mail->pdfPath)
            ->as('Contrat-'.$contract->season.'-'.$contract->client->clientNumber.'.pdf')->withMime('application/pdf'));
        expect($mail->render())->toContain($contract->client->displayName(), $contract->season);

        return $mail->hasTo('client@example.com');
    });
    Mail::assertSentCount(1);
    expect($contract->refresh()->document_mail_status)->toBe('sent')->and($contract->document_sent_at?->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($contract->document_sent_by)->toBe($admin->id);
    $this->actingAs($admin)->get(route('contracts.show', $contract))->assertInertia(fn (Assert $page): Assert => $page
        ->where('contract.mail.status', 'sent')->where('contract.mail.sentBy', $admin->name));
    $this->get(route('contracts.index'))->assertInertia(fn (Assert $page): Assert => $page->where('contracts.data.0.mail.status', 'sent'));
})->with(['fr', 'en']);

it('skips changed or erased recipients before sending any PDF', function (string $change): void {
    $admin = User::factory()->admin()->create();
    [$contract, $job] = queuedContractEmail($admin);
    if ($change === 'anonymized') {
        $contract->client->forceFill(['anonymized_at' => now(), 'email' => null])->save();
    } elseif ($change === 'email') {
        $contract->client->update(['email' => 'different@example.com']);
    } elseif ($change === 'client') {
        $contract->update(['client_id' => Client::factory()->forOrganization($admin->organization)->create()->id]);
    } else {
        $contract->delete();
    }
    Mail::fake();
    Http::fake();
    $job->handle(app(DeliverContractEmail::class));
    Mail::assertNothingSent();
    Http::assertNothingSent();
    if ($change !== 'deleted') {
        expect($contract->refresh()->document_mail_status)->toBe('skipped')->and($contract->document_sent_at)->toBeNull();
    }
})->with(['anonymized', 'email', 'client', 'deleted']);

it('retries PDF failures without recording an email and marks exhausted attempts as failed', function (): void {
    $admin = User::factory()->admin()->create();
    [$contract, $job] = queuedContractEmail($admin);
    Http::fake(['*/forms/chromium/convert/html' => Http::response('failed', 500)]);
    Mail::fake();
    expect(fn () => $job->handle(app(DeliverContractEmail::class)))->toThrow(RequestException::class);
    expect($contract->refresh()->document_mail_status)->toBe('queued')->and($contract->document_sent_at)->toBeNull();
    $job->failed(null);
    expect($contract->refresh()->document_mail_status)->toBe('failed');
    Mail::assertNothingSent();
});

it('does not retry an uncertain SMTP attempt or an older request', function (): void {
    $admin = User::factory()->admin()->create();
    [$contract, $job] = queuedContractEmail($admin);
    Storage::fake(config()->string('filesystems.default'));
    Http::fake(['*/forms/chromium/convert/html' => Http::response('%PDF-1.4 contract', 200)]);
    Exceptions::fake();
    Mail::shouldReceive('to')->once()->with('client@example.com')->andReturnSelf();
    Mail::shouldReceive('sendNow')->once()->andThrow(new RuntimeException('SMTP acceptance unknown'));
    $job->handle(app(DeliverContractEmail::class));
    expect($contract->refresh()->document_mail_status)->toBe('uncertain')->and($contract->document_sent_at)->toBeNull();
    $job->handle(app(DeliverContractEmail::class));
    Exceptions::assertReported(RuntimeException::class);
    $contract->forceFill(['document_mail_status' => 'queued', 'document_mail_request_id' => (string) str()->uuid()])->save();
    $job->handle(app(DeliverContractEmail::class));
    $job->failed(new RuntimeException('Old worker'));
    expect($contract->refresh()->document_mail_status)->toBe('queued');
});

it('marks an interrupted transport uncertain without sending again', function (): void {
    $admin = User::factory()->admin()->create();
    [$contract, $job] = queuedContractEmail($admin);
    $contract->forceFill(['document_mail_status' => 'sending'])->save();
    Mail::fake();
    $job->handle(app(DeliverContractEmail::class));
    expect($contract->refresh()->document_mail_status)->toBe('uncertain');
    Mail::assertNothingSent();
});
