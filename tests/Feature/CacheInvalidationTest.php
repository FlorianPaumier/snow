<?php

declare(strict_types=1);

use App\Actions\CreateClient;
use App\Actions\MarkPaymentPaid;
use App\Enums\ClientType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractService;
use App\Models\Payment;
use App\Models\User;
use App\ValueObjects\Season;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();

    $this->client = Client::factory()->forOrganization($this->admin->organization)->create();

    $this->contract = Contract::query()->create([
        'season' => Season::current()->label,
        'client_id' => $this->client->id,
        'organization_id' => $this->admin->organization_id,
    ]);

    ContractService::query()->create([
        'contract_id' => $this->contract->id,
        'organization_id' => $this->admin->organization_id,
        'label' => 'Déneigement entrée',
        'amount' => 50000,
        'position' => 1,
    ]);

    $this->payment = Payment::query()->create([
        'contract_id' => $this->contract->id,
        'organization_id' => $this->admin->organization_id,
        'slot' => PaymentSlot::SLOT_SECOND_INSTALLMENT,
        'amount' => 50000,
        'paid' => false,
    ]);
});

it('refreshes the dashboard summary after marking a payment paid', function (): void {
    $season = Season::current()->label;

    // First visit seeds the cache with the unpaid state...
    $this->actingAs($this->admin)
        ->get(route('dashboard', ['season' => $season]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('summary.collected', 0));

    // The mutation flushes the organization's cache...
    resolve(MarkPaymentPaid::class)->handle($this->payment, PaymentMethod::PAYMENT_CASH);

    // The second visit must recompute, not replay the stale cache...
    $this->actingAs($this->admin)
        ->get(route('dashboard', ['season' => $season]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('summary.collected', 50000));
});

it('refreshes the clients index after creating a client', function (): void {
    // Seed the list page cache...
    $this->actingAs($this->admin)
        ->get(route('clients.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('clients.total', 1));

    // Create a client through the action (as the controller does)...
    resolve(CreateClient::class)->handle([
        'type' => ClientType::CLIENT_INDIVIDUAL,
        'firstName' => 'Nouvelle',
        'lastName' => 'Cliente',
        'email' => 'nouvelle@example.com',
        'addressStreet' => '12 Rue Neuve',
        'addressCity' => 'Québec',
        'addressPostalCode' => 'G1R 1R1',
        'addressCountry' => 'CA',
    ]);

    $this->actingAs($this->admin)
        ->get(route('clients.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('clients.total', 2));
});

it('does not leak one organization cache into another', function (): void {
    $otherAdmin = User::factory()->admin()->create();

    // Seed this organization's dashboard (collected: 0)...
    $this->actingAs($this->admin)
        ->get(route('dashboard', ['season' => Season::current()->label]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('summary.collected', 0));

    // The other organization mutates its own data: its flush must not
    // hand this organization stale-fresh entries either way...
    $otherClient = Client::factory()->forOrganization($otherAdmin->organization)->create();
    $otherContract = Contract::query()->create([
        'season' => Season::current()->label,
        'client_id' => $otherClient->id,
        'organization_id' => $otherAdmin->organization_id,
    ]);
    ContractService::query()->create([
        'contract_id' => $otherContract->id,
        'organization_id' => $otherAdmin->organization_id,
        'label' => 'Service',
        'amount' => 10000,
        'position' => 1,
    ]);

    $this->actingAs($otherAdmin)
        ->get(route('dashboard', ['season' => Season::current()->label]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('summary.revenue', 10000));

    // This organization still sees its own, untouched data...
    $this->actingAs($this->admin)
        ->get(route('dashboard', ['season' => Season::current()->label]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('summary.revenue', 50000)
            ->where('summary.collected', 0));
});

it('flushes the organization cache when a Stripe webhook arrives', function (): void {
    // No STRIPE_WEBHOOK_SECRET in the test environment, so the signature
    // middleware is not registered — the endpoint is directly callable...
    $this->admin->organization->forceFill(['stripe_id' => 'cus_test_123'])->save();

    // Seed the subscription summary cache...
    $this->actingAs($this->admin)
        ->get(route('dashboard', ['season' => Season::current()->label]))
        ->assertOk();

    $versionKey = sprintf('tenant:cache:version:%s', $this->admin->organization_id);

    expect(Cache::has($versionKey))->toBeTrue();

    // A subscription update webhook for this organization's customer,
    // shaped as Stripe sends it (Cashier reads items.data[0])...
    $this->postJson(route('cashier.webhook'), [
        'id' => 'evt_test',
        'type' => 'customer.subscription.updated',
        'data' => [
            'object' => [
                'id' => 'sub_test',
                'customer' => 'cus_test_123',
                'status' => 'active',
                'items' => [
                    'data' => [
                        [
                            'id' => 'si_test',
                            'price' => ['id' => 'price_test', 'product' => 'prod_test'],
                            'quantity' => 1,
                        ],
                    ],
                ],
                'metadata' => ['name' => 'starter'],
            ],
        ],
    ])->assertOk();

    // The version token rotated: every entry of the organization is
    // orphaned...
    expect(Cache::has($versionKey))->toBeFalse();
});

it('accepts a webhook for an unknown customer without flushing anything', function (): void {
    $this->admin->organization->forceFill(['stripe_id' => 'cus_test_123'])->save();

    // Seed the cache and capture the version token...
    $this->actingAs($this->admin)
        ->get(route('dashboard', ['season' => Season::current()->label]))
        ->assertOk();

    $versionKey = sprintf('tenant:cache:version:%s', $this->admin->organization_id);

    expect(Cache::has($versionKey))->toBeTrue();

    // The customer belongs to no billable in this install: the webhook is
    // acknowledged and the cache left untouched...
    $this->postJson(route('cashier.webhook'), [
        'id' => 'evt_unknown',
        'type' => 'customer.subscription.updated',
        'data' => [
            'object' => [
                'id' => 'sub_unknown',
                'customer' => 'cus_nope',
                'status' => 'active',
                'items' => [
                    'data' => [
                        [
                            'id' => 'si_unknown',
                            'price' => ['id' => 'price_test', 'product' => 'prod_test'],
                            'quantity' => 1,
                        ],
                    ],
                ],
                'metadata' => ['name' => 'starter'],
            ],
        ],
    ])->assertOk();

    expect(Cache::has($versionKey))->toBeTrue();
});

it('accepts a webhook without a customer id without flushing anything', function (): void {
    $this->admin->organization->forceFill(['stripe_id' => 'cus_test_123'])->save();

    $this->actingAs($this->admin)
        ->get(route('dashboard', ['season' => Season::current()->label]))
        ->assertOk();

    $versionKey = sprintf('tenant:cache:version:%s', $this->admin->organization_id);

    // Some webhook types carry no customer (e.g. invoice events): the
    // controller must acknowledge them and touch no organization...
    $this->postJson(route('cashier.webhook'), [
        'id' => 'evt_invoice',
        'type' => 'invoice.payment_succeeded',
        'data' => [
            'object' => [
                'id' => 'in_test',
                'amount_paid' => 5000,
            ],
        ],
    ])->assertOk();

    expect(Cache::has($versionKey))->toBeTrue();
});
