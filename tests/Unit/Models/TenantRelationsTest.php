<?php

declare(strict_types=1);

use App\Enums\PaymentSlot;
use App\Mail\LoginChallengeCodeMail;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractService;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

beforeEach(function (): void {
    $this->user = User::factory()->create();

    Auth::login($this->user);
});

it('resolves a client latest contract', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create();

    $older = Contract::query()->create(['season' => '2023-2024', 'client_id' => $client->id, 'organization_id' => $client->organization_id]);
    $newer = Contract::query()->create(['season' => '2025-2026', 'client_id' => $client->id, 'organization_id' => $client->organization_id]);

    expect($client->lastContract()->is($newer))->toBeTrue()
        ->and($client->lastContract()->is($older))->toBeFalse();
});

it('resolves a contract service relations', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create();

    $contract = Contract::query()->create(['season' => '2025-2026', 'client_id' => $client->id, 'organization_id' => $client->organization_id]);

    $service = ContractService::query()->create([
        'contract_id' => $contract->id,
        'organization_id' => $contract->organization_id,
        'label' => 'Déneigement entrée',
        'amount' => 50000,
        'position' => 1,
    ]);

    expect($service->contract()->is($contract))->toBeTrue()
        ->and($service->organization()->is($this->user->organization))->toBeTrue();
});

it('resolves a payment relations', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create();

    $contract = Contract::query()->create(['season' => '2025-2026', 'client_id' => $client->id, 'organization_id' => $client->organization_id]);

    $payment = Payment::query()->create([
        'contract_id' => $contract->id,
        'organization_id' => $contract->organization_id,
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 10000,
        'paid' => false,
    ]);

    expect($payment->contract()->is($contract))->toBeTrue()
        ->and($payment->organization()->is($this->user->organization))->toBeTrue();
});

it('resolves an organization relations', function (): void {
    $organization = $this->user->organization;

    expect($organization->users()->count())->toBeInt()
        ->and($organization->clients()->count())->toBeInt()
        ->and($organization->contracts()->count())->toBeInt()
        ->and($organization->subscriptions())->toBeInstanceOf(HasMany::class);
});

it('renders the login challenge mailable', function (): void {
    $mail = new LoginChallengeCodeMail('654321');

    expect($mail->envelope()->subject)->toBe('Your login code')
        ->and($mail->content()->markdown)->toBe('mail.login-challenge-code')
        ->and($mail->content()->with)->toBe(['code' => '654321'])
        ->and($mail->render())->toContain('654321');
});
