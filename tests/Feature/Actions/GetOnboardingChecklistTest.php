<?php

declare(strict_types=1);

use App\Actions\GetOnboardingChecklist;
use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\User;
use App\ValueObjects\Season;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
});

it('lists every step as incomplete for a fresh organization', function (): void {
    $checklist = resolve(GetOnboardingChecklist::class)->handle($this->admin);

    expect($checklist)->toBeArray()
        ->and($checklist['incompleteCount'])->toBe(4)
        ->and($checklist['steps'])->each(
            fn ($step) => $step->completed->toBeFalse(),
        );
});

it('marks a step completed once the organization has done it', function (): void {
    $client = Client::factory()->forOrganization($this->admin->organization)->create();
    $contract = Contract::query()->create(['season' => Season::current()->label, 'client_id' => $client->id, 'organization_id' => $this->admin->organization_id]);
    Payment::query()->create(['contract_id' => $contract->id, 'organization_id' => $this->admin->organization_id, 'slot' => PaymentSlot::SLOT_DEPOSIT, 'amount' => 10000, 'paid' => true, 'paid_at' => now()]);
    User::factory()->forOrganization($this->admin->organization)->create();

    $steps = collect(resolve(GetOnboardingChecklist::class)->handle($this->admin)['steps'])->keyBy('key');

    expect($steps['first_client']['completed'])->toBeTrue()
        ->and($steps['first_contract']['completed'])->toBeTrue()
        ->and($steps['first_paid_installment']['completed'])->toBeTrue()
        ->and($steps['invite_teammate']['completed'])->toBeTrue();
});

it('does not count an unpaid installment toward the payment step', function (): void {
    $client = Client::factory()->forOrganization($this->admin->organization)->create();
    $contract = Contract::query()->create(['season' => Season::current()->label, 'client_id' => $client->id, 'organization_id' => $this->admin->organization_id]);
    Payment::query()->create(['contract_id' => $contract->id, 'organization_id' => $this->admin->organization_id, 'slot' => PaymentSlot::SLOT_DEPOSIT, 'amount' => 10000, 'paid' => false]);

    $steps = collect(resolve(GetOnboardingChecklist::class)->handle($this->admin)['steps'])->keyBy('key');

    expect($steps['first_paid_installment']['completed'])->toBeFalse();
});

it('links each step to the page where the action is performed', function (): void {
    $steps = collect(resolve(GetOnboardingChecklist::class)->handle($this->admin)['steps'])->keyBy('key');

    expect($steps['first_client']['href'])->toBe(route('clients.create'))
        ->and($steps['first_contract']['href'])->toBe(route('contracts.create'))
        ->and($steps['first_paid_installment']['href'])->toBe(route('dashboard'))
        ->and($steps['invite_teammate']['href'])->toBe(route('users.create'));
});

it('never suggests an incomplete step the user cannot perform', function (): void {
    // Any non-admin in the org implies a second user, so the invite step
    // is completed — but the three action steps are not, and none of
    // them may be suggested to a worker (no manage gates)...
    $worker = User::factory()->worker()->forOrganization($this->admin->organization)->create();

    $checklist = resolve(GetOnboardingChecklist::class)->handle($worker);

    expect($checklist['incompleteCount'])->toBe(0)
        ->and($checklist['steps'])->each(
            fn ($step) => $step->completed->toBeTrue(),
        );
});

it('returns null without an authenticated organization user', function (): void {
    expect(resolve(GetOnboardingChecklist::class)->handle())->toBeNull();
});
