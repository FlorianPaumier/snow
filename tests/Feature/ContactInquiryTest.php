<?php

declare(strict_types=1);

use App\Actions\SubmitContactInquiry;
use App\Enums\ContactInquiryStatus;
use App\Jobs\SendContactInquiry;
use App\Models\ContactInquiry;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

it('persists a contact inquiry and queues its delivery', function (): void {
    Queue::fake();
    Mail::fake();

    $response = $this->postJson('/contact', [
        'name' => 'Dana Kowalski',
        'email' => 'dana@example.com',
        'company' => 'Twin Cities Snow Co.',
        'fleetSize' => '4-10',
        'message' => 'We run six trucks across the metro and need dispatch.',
    ]);

    $response->assertOk()->assertJson(['ok' => true]);

    $inquiry = ContactInquiry::query()->sole();

    expect($inquiry->name)->toBe('Dana Kowalski')
        ->and($inquiry->email)->toBe('dana@example.com')
        ->and($inquiry->company)->toBe('Twin Cities Snow Co.')
        ->and($inquiry->fleetSize)->toBe('4-10')
        ->and($inquiry->message)->toBe('We run six trucks across the metro and need dispatch.')
        ->and($inquiry->status)->toBe(ContactInquiryStatus::STATUS_PENDING);

    Queue::assertPushed(
        SendContactInquiry::class,
        fn (SendContactInquiry $job): bool => $job->inquiryId === $inquiry->id,
    );

    Mail::assertNothingQueued();
    Mail::assertNothingSent();
});

it('persists nothing when the contact inquiry payload is invalid', function (): void {
    Queue::fake();
    Mail::fake();

    $response = $this->postJson('/contact', [
        'name' => 'D',
        'email' => 'not-an-email',
        'message' => 'short',
    ]);

    $response->assertUnprocessable();

    $this->assertDatabaseCount(ContactInquiry::class, 0);

    Queue::assertNothingPushed();
    Mail::assertNothingSent();
});

it('keeps the inquiry row even when the queue dispatch fails', function (): void {
    // Simulate a dead queue connection: the bus dispatch throws after the
    // action has persisted the lead, and the row must survive.
    $bus = Mockery::mock(Dispatcher::class);
    $bus->shouldReceive('dispatch')->once()->andThrow(new RuntimeException('Queue connection failed'));
    app()->instance(Dispatcher::class, $bus);

    $action = resolve(SubmitContactInquiry::class);

    expect(fn (): ContactInquiry => $action->handle([
        'name' => 'Dana Kowalski',
        'email' => 'dana@example.com',
        'company' => null,
        'fleetSize' => null,
        'message' => 'We run six trucks across the metro and need dispatch.',
    ]))->toThrow(RuntimeException::class, 'Queue connection failed');

    $this->assertDatabaseCount(ContactInquiry::class, 1);
});
