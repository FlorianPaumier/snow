<?php

declare(strict_types=1);

use App\Actions\UpdateContract;
use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

// The organization scope requires an authenticated user to see any data;
// records must belong to that user's organization to be visible...
beforeEach(function (): void {
    $this->user = User::factory()->create();

    Auth::login($this->user);
});

it('syncs services keeping order as position and updates unpaid amounts only', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();
    $kept = $contract->services()->create(['label' => 'Déneigement entrée', 'amount' => 40000, 'position' => 1]);
    $removed = $contract->services()->create(['label' => 'Déglaçage', 'amount' => 5000, 'position' => 2]);
    $deposit = $contract->payments()->create(['slot' => PaymentSlot::SLOT_DEPOSIT, 'amount' => 15000, 'paid' => false, 'method' => null, 'paid_at' => null]);

    $action = resolve(UpdateContract::class);
    $action->handle($contract, [
        'clientId' => $client->id,
        'season' => $contract->season,
        'services' => [
            ['id' => $kept->id, 'label' => 'Déneigement entrée', 'amount' => 35000],
            ['label' => 'Stationnement', 'amount' => 5000],
        ],
        'payments' => [
            'deposit' => 20000,
            'first' => 20000,
            'second' => 0,
            'balance' => 0,
        ],
    ]);

    $contract->refresh();

    expect($contract->services->pluck('label')->all())->toBe(['Déneigement entrée', 'Stationnement'])
        ->and($contract->services->pluck('position')->all())->toBe([1, 2])
        ->and($removed->fresh())->toBeNull()
        ->and($deposit->fresh()->amount)->toBe(20000);
});
