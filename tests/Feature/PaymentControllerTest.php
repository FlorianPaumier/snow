<?php

declare(strict_types=1);

use App\Enums\PaymentMethod;
use App\Enums\PaymentSlot;
use App\Mail\InstallmentReminderMail;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

function unpaidPayment(User $admin): Payment
{
    $client = Client::factory()->forOrganization($admin->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();

    return $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 15000,
        'paid' => false,
        'method' => null,
        'paid_at' => null,
    ]);
}

it('marks a payment as paid with the method and today as the collection date', function (): void {
    $admin = User::factory()->admin()->create();
    $payment = unpaidPayment($admin);

    $this->actingAs($admin)
        ->post(route('payments.mark-paid', $payment), ['method' => PaymentMethod::PAYMENT_VIREMENT->value])
        ->assertRedirect(route('contracts.show', ['contract' => $payment->contract_id]));

    $payment->refresh();
    expect($payment->paid)->toBeTrue()
        ->and($payment->method)->toBe(PaymentMethod::PAYMENT_VIREMENT)
        // Time is frozen by the test bootstrap, so paid_at is "today"...
        ->and($payment->paid_at?->isSameDay(now()))->toBeTrue()
        ->and($payment->paid_at)->not->toBeNull();
});

it('requires a valid method', function (): void {
    $admin = User::factory()->admin()->create();
    $payment = unpaidPayment($admin);

    $this->actingAs($admin)
        ->post(route('payments.mark-paid', $payment), ['method' => 'bitcoin'])
        ->assertSessionHasErrors('method');

    $this->actingAs($admin)
        ->post(route('payments.mark-paid', $payment), [])
        ->assertSessionHasErrors('method');
});

it('refuses to mark an already paid payment', function (): void {
    $admin = User::factory()->admin()->create();
    $payment = unpaidPayment($admin);
    $payment->update([
        'paid' => true,
        'method' => PaymentMethod::PAYMENT_CASH,
        'paid_at' => now(),
    ]);

    $this->actingAs($admin)
        ->post(route('payments.mark-paid', $payment), ['method' => PaymentMethod::PAYMENT_CASH->value])
        ->assertSessionHasErrors('payment');
});

it('unmarks a paid payment', function (): void {
    $admin = User::factory()->admin()->create();
    $payment = unpaidPayment($admin);
    $payment->update([
        'paid' => true,
        'method' => PaymentMethod::PAYMENT_CASH,
        'paid_at' => now(),
    ]);

    $this->actingAs($admin)
        ->post(route('payments.unmark', $payment))
        ->assertRedirect(route('contracts.show', ['contract' => $payment->contract_id]));

    $payment->refresh();
    expect($payment->paid)->toBeFalse()
        ->and($payment->method)->toBeNull()
        ->and($payment->paid_at)->toBeNull();
});

it('refuses to unmark a payment that is not paid', function (): void {
    $admin = User::factory()->admin()->create();
    $payment = unpaidPayment($admin);

    $this->actingAs($admin)
        ->post(route('payments.unmark', $payment))
        ->assertSessionHasErrors('payment');
});

it('prevents non-admins from marking or unmarking payments', function (): void {
    $user = User::factory()->create();
    $payment = unpaidPayment($user);

    $this->actingAs($user)
        ->post(route('payments.mark-paid', $payment), ['method' => PaymentMethod::PAYMENT_CASH->value])
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('payments.unmark', $payment))
        ->assertForbidden();

    expect($payment->fresh()->paid)->toBeFalse();
});

it('blocks the manual payment reminder on the free plan with an upgrade prompt', function (): void {
    $admin = User::factory()->admin()->create();
    $admin->organization->forceFill(['trial_ends_at' => now()->subDay()])->save();
    $payment = unpaidPayment($admin);

    Mail::fake();

    $this->actingAs($admin)
        ->fromRoute('dashboard')
        ->post(route('payments.send-reminder', $payment))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', __('statuses.feature_locked'));

    Mail::assertNothingQueued();
    expect($payment->fresh()->last_reminder_at)->toBeNull();
});

it('carries the feature lock to the error bag for inertia requests', function (): void {
    $admin = User::factory()->admin()->create();
    $admin->organization->forceFill(['trial_ends_at' => now()->subDay()])->save();
    $payment = unpaidPayment($admin);

    Mail::fake();

    // Inertia requests get the upgrade prompt in the shared error bag
    // under the `featureLocked` key, which the dashboard alert reads...
    $this->actingAs($admin)
        ->fromRoute('dashboard')
        ->withHeaders(['X-Inertia' => 'true'])
        ->post(route('payments.send-reminder', $payment))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasErrors(['featureLocked' => __('statuses.feature_locked', [], 'fr')]);

    Mail::assertNothingQueued();
    expect($payment->fresh()->last_reminder_at)->toBeNull();
});

it('sends the manual payment reminder while on the signup trial', function (): void {
    $admin = User::factory()->admin()->create(); // trial by default...
    $client = Client::factory()->forOrganization($admin->organization)->create([
        'email' => 'client@example.com',
    ]);
    $contract = Contract::factory()->forClient($client)->create();
    $payment = $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 15000,
        'paid' => false,
        'method' => null,
        'paid_at' => null,
    ]);

    Mail::fake();

    $this->actingAs($admin)
        ->fromRoute('dashboard')
        ->post(route('payments.send-reminder', $payment))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', __('statuses.payment_reminder_sent'));

    Mail::assertQueued(InstallmentReminderMail::class);
});
