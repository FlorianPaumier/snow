<?php

declare(strict_types=1);

use App\Actions\GenerateContractPdf;
use App\Actions\UpdateContract;
use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
            'deposit' => ['amount' => 20000, 'dueDate' => '2026-12-01'],
            'first' => ['amount' => 20000, 'dueDate' => null],
            'second' => ['amount' => 0, 'dueDate' => null],
            'balance' => ['amount' => 0, 'dueDate' => null],
        ],
    ]);

    $contract->refresh();

    expect($contract->services->pluck('label')->all())->toBe(['Déneigement entrée', 'Stationnement'])
        ->and($contract->services->pluck('position')->all())->toBe([1, 2])
        ->and($removed->fresh())->toBeNull()
        ->and($deposit->fresh()->amount)->toBe(20000)
        ->and($deposit->fresh()->due_date->toDateString())->toBe('2026-12-01');
});

it('deletes the stored contract pdf when the contract changes', function (): void {
    $disk = Storage::fake((string) config('filesystems.default'));
    $client = Client::factory()->forOrganization($this->user->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();
    // Another organization's contract PDF must survive...
    $other = Contract::factory()->create();

    foreach ([$contract, $other] as $contractWithPdf) {
        $disk->put(
            GenerateContractPdf::pdfPath($contractWithPdf->id),
            '%PDF-1.4 fake bytes',
        );
    }

    resolve(UpdateContract::class)->handle($contract, [
        'clientId' => $client->id,
        'season' => $contract->season,
        'services' => [
            ['label' => 'Déneigement entrée', 'amount' => 35000],
        ],
        'payments' => [],
    ]);

    $disk->assertMissing(GenerateContractPdf::pdfPath($contract->id));
    $disk->assertExists(GenerateContractPdf::pdfPath($other->id));
});
