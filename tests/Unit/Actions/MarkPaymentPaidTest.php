<?php

declare(strict_types=1);

use App\Actions\MarkPaymentPaid;
use App\Enums\PaymentMethod;
use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

// The organization scope requires an authenticated user to see any data;
// contracts and payments must belong to that user's organization...
beforeEach(function (): void {
    $this->user = User::factory()->create();

    Auth::login($this->user);
});

it('marks a payment paid with the method and the current date', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();
    $payment = $contract->payments()->create([
        'organization_id' => $contract->organization_id,
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 15000,
        'paid' => false,
        'method' => null,
        'paid_at' => null,
    ]);

    $action = resolve(MarkPaymentPaid::class);
    $action->handle($payment, PaymentMethod::PAYMENT_VIREMENT);

    $payment->refresh();
    expect($payment->paid)->toBeTrue()
        ->and($payment->method)->toBe(PaymentMethod::PAYMENT_VIREMENT)
        ->and($payment->paid_at?->isSameDay(now()))->toBeTrue()
        ->and($payment->paid_at)->not->toBeNull();
});

it('refuses an already paid payment', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();
    $payment = $contract->payments()->create([
        'organization_id' => $contract->organization_id,
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 15000,
        'paid' => true,
        'method' => PaymentMethod::PAYMENT_CASH,
        'paid_at' => now(),
    ]);

    $action = resolve(MarkPaymentPaid::class);

    expect(fn (): bool => $action->handle($payment, PaymentMethod::PAYMENT_CASH))
        ->toThrow(ValidationException::class);
});
