<?php

declare(strict_types=1);

use App\Actions\SendPaymentReminder;
use App\Enums\PaymentSlot;
use App\Mail\InstallmentReminderMail;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

// The organization scope requires an authenticated user to see any data;
// contracts and payments must belong to that user's organization...
beforeEach(function (): void {
    $this->user = User::factory()->create();

    Auth::login($this->user);
});

/**
 * An unpaid installment with a due date, ready to be reminded.
 */
function reminderPayment(Client $client, array $attributes = []): Payment
{
    $contract = Contract::factory()->forClient($client)->create();

    return $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_FIRST_INSTALLMENT,
        'amount' => 20000,
        'paid' => false,
        'due_date' => '2026-12-01',
        ...$attributes,
    ]);
}

it('sends a queued reminder to the client and records the send', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create([
        'email' => 'client@example.com',
    ]);
    $payment = reminderPayment($client);
    $action = resolve(SendPaymentReminder::class);

    Mail::fake();

    $action->handle($payment);

    Mail::assertQueued(InstallmentReminderMail::class, fn (InstallmentReminderMail $mail): bool => $mail->hasTo('client@example.com')
        && $mail->reminder['clientName'] === $client->displayName()
        && $mail->reminder['organizationName'] === $this->user->organization->name
        && $mail->reminder['replyTo'] === [$this->user->email]);

    $payment->refresh();
    expect($payment->last_reminder_at?->equalTo(now()->startOfSecond()))->toBeTrue()
        ->and($payment->automated_reminders_sent)->toBe(0);
});

it('refuses to remind when the client has no email on file', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->withoutEmail()->create();
    $payment = reminderPayment($client);
    $action = resolve(SendPaymentReminder::class);

    Mail::fake();

    expect(fn (): SendPaymentReminder => $action->handle($payment))
        ->toThrow(ValidationException::class, __('payments.no_email'));

    Mail::assertNothingQueued();
    expect($payment->fresh()->last_reminder_at)->toBeNull();
});

it('refuses to remind an already collected installment', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create();
    $payment = reminderPayment($client, ['paid' => true, 'method' => 'cash', 'paid_at' => now()]);
    $action = resolve(SendPaymentReminder::class);

    Mail::fake();

    expect(fn (): SendPaymentReminder => $action->handle($payment))
        ->toThrow(ValidationException::class, __('payments.already_paid'));

    Mail::assertNothingQueued();
});
