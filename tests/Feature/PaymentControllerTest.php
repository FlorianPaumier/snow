<?php

declare(strict_types=1);

use App\Enums\PaymentMethod;
use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\User;

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
