<?php

declare(strict_types=1);

use App\Actions\DeleteContract;
use App\Enums\PaymentMethod;
use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

// The organization scope requires an authenticated user to see any data;
// records must belong to that user's organization to be visible...
beforeEach(function (): void {
    $this->user = User::factory()->create();

    Auth::login($this->user);
});

it('deletes a contract without paid installments and cascades its children', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();
    $service = $contract->services()->create(['label' => 'Entrée', 'amount' => 40000, 'position' => 1]);
    $payment = $contract->payments()->create(['slot' => PaymentSlot::SLOT_DEPOSIT, 'amount' => 40000, 'paid' => false, 'method' => null, 'paid_at' => null]);

    $action = resolve(DeleteContract::class);
    $action->handle($contract);

    expect($contract->fresh())->toBeNull()
        ->and($service->fresh())->toBeNull()
        ->and($payment->fresh())->toBeNull();
});

it('refuses to delete a contract with paid installments', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();
    $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 15000,
        'paid' => true,
        'method' => PaymentMethod::PAYMENT_CASH,
        'paid_at' => now(),
    ]);

    $action = resolve(DeleteContract::class);

    expect(fn (): bool => $action->handle($contract))
        ->toThrow(ValidationException::class)
        ->and($contract->fresh())->not->toBeNull();
});
