<?php

declare(strict_types=1);

use App\Actions\CreateContract;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use App\ValueObjects\Season;
use Illuminate\Support\Facades\Auth;

// The organization scope requires an authenticated user to see any data;
// clients must belong to that user's organization to be visible...
beforeEach(function (): void {
    $this->user = User::factory()->create();

    Auth::login($this->user);
});

it('creates a contract with ordered services and the four payment slots', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create();
    $action = resolve(CreateContract::class);

    $contract = $action->handle([
        'client' => $client,
        'season' => Season::current()->label,
        'services' => [
            ['label' => 'Déneigement entrée', 'amount' => 35000],
            ['label' => 'Déglaçage', 'amount' => 10000],
        ],
        'payments' => [
            'deposit' => 15000,
            'first' => 10000,
            'second' => 10000,
            'balance' => 10000,
        ],
    ]);

    expect($contract)->toBeInstanceOf(Contract::class)
        ->and($contract->client_id)->toBe($client->id)
        ->and($contract->season)->toBe(Season::current()->label)
        ->and($contract->services)->toHaveCount(2)
        ->and($contract->services->pluck('position')->all())->toBe([1, 2])
        ->and($contract->payments)->toHaveCount(4)
        ->and($contract->payments->pluck('slot.value')->sort()->values()->all())
        ->toBe(['balance', 'deposit', 'first', 'second'])
        ->and($contract->payments->every(fn ($payment): bool => $payment->paid === false))->toBeTrue();
});
