<?php

declare(strict_types=1);

use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractService;
use App\Models\Payment;
use App\Models\User;
use App\ValueObjects\Season;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
});

it('renders cached dashboard data when cached objects cannot be unserialized', function (): void {
    config()->set('cache.stores.array.serialize', true);
    Cache::purge('array');

    try {
        $this->actingAs($this->admin);

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('contracts.index'))->assertOk();
    } finally {
        config()->set('cache.stores.array.serialize', false);
        Cache::purge('array');
    }
});

it('renders the dashboard with summary data for admins', function (): void {
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('season', Season::current()->label)
            ->has('seasons')
            ->has('summary')
            ->has('unpaidInstallments')
            ->has('seasonRecap')
            ->has('revenueEvolution')
            ->has('clientEvolution')
            ->where('slot', PaymentSlot::SLOT_SECOND_INSTALLMENT->value));
});

it('renders the dashboard for workers and regular users', function (): void {
    $user = User::factory()->create();

    foreach ([[$this->admin, 'admin'], [$user, 'user']] as [$actingUser, $role]) {
        $this->actingAs($actingUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('dashboard'));
    }

    expect(true)->toBeTrue();
});

it('shares the onboarding checklist while steps remain', function (): void {
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('onboardingChecklist.steps', 4)
            ->where('onboardingChecklist.incompleteCount', 4)
            ->where('onboardingChecklist.steps.0.key', 'first_client')
            ->where('onboardingChecklist.steps.0.href', route('clients.create'))
            ->where('onboardingChecklist.steps.0.completed', false));
});

it('omits the onboarding checklist once every step is completed', function (): void {
    $client = Client::factory()->forOrganization($this->admin->organization)->create();
    $contract = Contract::query()->create(['season' => Season::current()->label, 'client_id' => $client->id, 'organization_id' => $this->admin->organization_id]);
    Payment::query()->create(['contract_id' => $contract->id, 'organization_id' => $this->admin->organization_id, 'slot' => PaymentSlot::SLOT_DEPOSIT, 'amount' => 10000, 'paid' => true, 'paid_at' => now()]);
    User::factory()->forOrganization($this->admin->organization)->create();

    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->missing('onboardingChecklist'));
});

it('requires authentication', function (): void {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

it('requires email verification', function (): void {
    $unverified = User::factory()->unverified()->create();

    $this->actingAs($unverified)
        ->get(route('dashboard'))
        ->assertRedirectToRoute('verification.notice');
});

it('lands unverified users on the verify email notice instead of a redirect loop', function (): void {
    $unverified = User::factory()->unverified()->create();

    $this->actingAs($unverified)
        ->get(route('dashboard'));

    // The redirect target is auth-gated (not guest), so following it renders
    // the notice page rather than bouncing back to the dashboard...
    $this->get(route('verification.notice'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('user-email-verification-notification/create'));
});

it('accepts a season parameter', function (): void {
    $client = Client::factory()->forOrganization($this->admin->organization)->create();
    $contract = Contract::query()->create(['season' => '2024-2025', 'client_id' => $client->id, 'organization_id' => $this->admin->organization_id]);
    ContractService::query()->create(['contract_id' => $contract->id, 'organization_id' => $this->admin->organization_id, 'label' => 'Déneigement entrée', 'amount' => 50000, 'position' => 1]);

    $this->actingAs($this->admin)
        ->get(route('dashboard', ['season' => '2024-2025']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('season', '2024-2025')
            ->where('summary.revenue', 50000));
});

it('rejects an unknown season', function (): void {
    $this->actingAs($this->admin)
        ->get(route('dashboard', ['season' => '1899-1900']))
        ->assertSessionHasErrors('season');
});

it('defaults to the current season when the season parameter is absent', function (): void {
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('season', Season::current()->label));
});

it('accepts a slot parameter and defaults to the second installment', function (): void {
    $this->actingAs($this->admin)
        ->get(route('dashboard', ['slot' => 'first']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('slot', 'first'));
});

it('rejects an invalid slot', function (): void {
    $this->actingAs($this->admin)
        ->get(route('dashboard', ['slot' => 'not-a-slot']))
        ->assertSessionHasErrors('slot');
});

it('lists unpaid second installments with the client display name', function (): void {
    Date::setTestNow('2026-08-30');

    $debtor = Client::factory()->forOrganization($this->admin->organization)->create([
        'firstName' => 'Marc',
        'lastName' => 'Tremblay',
    ]);
    $contract = Contract::query()->create(['season' => Season::current()->label, 'client_id' => $debtor->id, 'organization_id' => $this->admin->organization_id]);
    Payment::query()->create(['contract_id' => $contract->id, 'organization_id' => $this->admin->organization_id, 'slot' => PaymentSlot::SLOT_SECOND_INSTALLMENT, 'amount' => 20000, 'paid' => false]);

    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('unpaidInstallments', 1)
            ->where('unpaidInstallments.0.amount', 20000)
            ->where('unpaidInstallments.0.contract_id', $contract->id)
            ->where('unpaidInstallments.0.client.displayName', 'Marc Tremblay')
            ->where('unpaidInstallments.0.client.clientNumber', $debtor->clientNumber));
});

it('feeds the evolution charts with one point per season, oldest first', function (): void {
    $client = Client::factory()->forOrganization($this->admin->organization)->create();

    foreach ([Season::fromYears(2024), Season::fromYears(2025)] as $season) {
        $contract = Contract::query()->create(['season' => $season->label, 'client_id' => $client->id, 'organization_id' => $this->admin->organization_id]);
        ContractService::query()->create(['contract_id' => $contract->id, 'organization_id' => $this->admin->organization_id, 'label' => 'Déneigement entrée', 'amount' => 40000, 'position' => 1]);
        Payment::query()->create(['contract_id' => $contract->id, 'organization_id' => $this->admin->organization_id, 'slot' => PaymentSlot::SLOT_DEPOSIT, 'amount' => 15000, 'paid' => true, 'method' => 'cash', 'paid_at' => now()]);
    }

    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('revenueEvolution', 2)
            ->where('revenueEvolution.0.season', '2024-2025')
            ->where('revenueEvolution.0.revenue', 40000)
            ->where('revenueEvolution.0.collected', 15000)
            ->has('clientEvolution', 1)
            ->where('clientEvolution.0.season', '2024-2025')
            ->where('clientEvolution.0.new_clients', 1)
            ->where('clientEvolution.0.total_clients', 1));
});
