<?php

declare(strict_types=1);

use App\Enums\PaymentSlot;
use App\Mail\InstallmentReminderMail;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

function reminderFixture(User $user): Payment
{
    $client = Client::factory()->forOrganization($user->organization)->create([
        'email' => 'client@example.com',
    ]);
    $contract = Contract::factory()->forClient($client)->create();

    return $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_FIRST_INSTALLMENT,
        'amount' => 20000,
        'paid' => false,
        'due_date' => now()->addDays(3),
    ]);
}

it('sends a reminder from the dashboard and records it', function (): void {
    $admin = User::factory()->admin()->create();
    $payment = reminderFixture($admin);

    Mail::fake();

    $this->actingAs($admin)
        ->post(route('payments.send-reminder', $payment))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', __('statuses.payment_reminder_sent'));

    Mail::assertQueued(InstallmentReminderMail::class, 1);

    expect($payment->fresh()->last_reminder_at?->equalTo(now()->startOfSecond()))->toBeTrue();
});

it('prevents non-admins from sending reminders', function (): void {
    $user = User::factory()->create();
    $payment = reminderFixture($user);

    Mail::fake();

    $this->actingAs($user)
        ->post(route('payments.send-reminder', $payment))
        ->assertForbidden();

    Mail::assertNothingQueued();
    expect($payment->fresh()->last_reminder_at)->toBeNull();
});

it('keeps reminder sending scoped to the current organization', function (): void {
    $admin = User::factory()->admin()->create();
    $otherUser = User::factory()->create();
    $otherPayment = reminderFixture($otherUser);

    Mail::fake();

    $this->actingAs($admin)
        ->post(route('payments.send-reminder', $otherPayment))
        ->assertNotFound();

    Mail::assertNothingQueued();
});

it('refuses a reminder when the client has no email', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->withoutEmail()->create();
    $contract = Contract::factory()->forClient($client)->create();
    $payment = $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_FIRST_INSTALLMENT,
        'amount' => 20000,
        'paid' => false,
        'due_date' => now()->addDays(3),
    ]);

    Mail::fake();

    $this->actingAs($admin)
        ->post(route('payments.send-reminder', $payment))
        ->assertSessionHasErrors('payment');

    Mail::assertNothingQueued();
});
