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

// Production sets STRIPE_WEBHOOK_SECRET, which makes Cashier attach its
// VerifyWebhookSignature middleware (from the controller constructor at
// dispatch time — not at route definition, so the tests below can set the
// secret per-test and exercise the real middleware). The helper signs a
// payload the way Stripe does: t=<unix time>,v1=<HMAC-SHA256 of "{t}.{payload}"
// with the secret (stripe-php's WebhookSignature scheme)...
function stripeSignature(string $payload, string $secret, ?int $timestamp = null): string
{
    $timestamp ??= time();

    $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

    return "t={$timestamp},v1={$signature}";
}

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
                'metadata' => ['name' => 'pro'],
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
                'metadata' => ['name' => 'pro'],
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

it('restores pro limits when a Stripe subscription becomes active', function (): void {
    $this->admin->organization->forceFill([
        'trial_ends_at' => now()->subDay(),
        'stripe_id' => 'cus_test_123',
    ])->save();

    // Seed the free-plan summary (expired trial, no subscription)...
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('auth.subscription.plan', 'free'));

    // A subscription update webhook for this organization's customer,
    // shaped as Stripe sends it (Cashier reads items.data[0])...
    $this->postJson(route('cashier.webhook'), [
        'id' => 'evt_access',
        'type' => 'customer.subscription.updated',
        'data' => [
            'object' => [
                'id' => 'sub_access',
                'customer' => 'cus_test_123',
                'status' => 'active',
                'items' => [
                    'data' => [
                        [
                            'id' => 'si_access',
                            'price' => ['id' => 'price_test', 'product' => 'prod_test'],
                            'quantity' => 1,
                        ],
                    ],
                ],
                'metadata' => ['name' => 'pro'],
            ],
        ],
    ])->assertOk();

    // The acting user instance persists across test requests, and its
    // cached organization still carries the pre-webhook subscriptions
    // relation. Real requests hydrate the user per request, so drop the
    // stale relation and let the recomputed summary query fresh...
    $this->admin->unsetRelation('organization');

    // The summary recomputed from the flushed cache: the org is on pro...
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('auth.subscription.plan', 'pro'));
});

it('flushes the organization cache when a signature-verified webhook arrives', function (): void {
    // Production configuration: the secret is set, so Cashier attaches the
    // signature middleware and the request must carry a valid Stripe
    // signature header...
    config(['cashier.webhook.secret' => 'whsec_test']);

    $this->admin->organization->forceFill(['stripe_id' => 'cus_test_123'])->save();

    // Seed the subscription summary cache...
    $this->actingAs($this->admin)
        ->get(route('dashboard', ['season' => Season::current()->label]))
        ->assertOk();

    $versionKey = sprintf('tenant:cache:version:%s', $this->admin->organization_id);

    expect(Cache::has($versionKey))->toBeTrue();

    // A subscription update webhook for this organization's customer,
    // signed exactly as Stripe signs it...
    $payload = json_encode([
        'id' => 'evt_signed',
        'type' => 'customer.subscription.updated',
        'data' => [
            'object' => [
                'id' => 'sub_signed',
                'customer' => 'cus_test_123',
                'status' => 'active',
                'items' => [
                    'data' => [
                        [
                            'id' => 'si_signed',
                            'price' => ['id' => 'price_test', 'product' => 'prod_test'],
                            'quantity' => 1,
                        ],
                    ],
                ],
                'metadata' => ['name' => 'pro'],
            ],
        ],
    ]);

    expect($payload)->toBeString();

    // The signature covers the exact request body bytes, so the raw signed
    // content is posted directly — postJson would re-encode its array
    // argument and the body would no longer match the signature...
    $this->call(
        'POST',
        route('cashier.webhook'),
        [],
        [],
        [],
        $this->transformHeadersToServerVars([
            'CONTENT_TYPE' => 'application/json',
            'Stripe-Signature' => stripeSignature($payload, 'whsec_test'),
        ]),
        $payload,
    )->assertOk();

    // The version token rotated: every entry of the organization is
    // orphaned...
    expect(Cache::has($versionKey))->toBeFalse();
});

it('rejects a webhook whose signature does not match the secret', function (): void {
    config(['cashier.webhook.secret' => 'whsec_test']);

    $this->admin->organization->forceFill(['stripe_id' => 'cus_test_123'])->save();

    $this->actingAs($this->admin)
        ->get(route('dashboard', ['season' => Season::current()->label]))
        ->assertOk();

    $versionKey = sprintf('tenant:cache:version:%s', $this->admin->organization_id);

    expect(Cache::has($versionKey))->toBeTrue();

    // A subscription update webhook for this organization's customer,
    // signed with the wrong secret...
    $payload = json_encode([
        'id' => 'evt_tampered',
        'type' => 'customer.subscription.updated',
        'data' => [
            'object' => [
                'id' => 'sub_tampered',
                'customer' => 'cus_test_123',
                'status' => 'active',
                'items' => [
                    'data' => [
                        [
                            'id' => 'si_tampered',
                            'price' => ['id' => 'price_test', 'product' => 'prod_test'],
                            'quantity' => 1,
                        ],
                    ],
                ],
                'metadata' => ['name' => 'pro'],
            ],
        ],
    ]);

    expect($payload)->toBeString();

    // Signed with the wrong secret: the middleware rejects the request
    // before the controller runs anything (Cashier maps the verification
    // failure to an access-denied 403)...
    $this->call(
        'POST',
        route('cashier.webhook'),
        [],
        [],
        [],
        $this->transformHeadersToServerVars([
            'CONTENT_TYPE' => 'application/json',
            'Stripe-Signature' => stripeSignature($payload, 'whsec_wrong'),
        ]),
        $payload,
    )->assertForbidden();

    // Nothing processed: the version token never rotated...
    expect(Cache::has($versionKey))->toBeTrue();
});

it('rejects a webhook whose signature timestamp is stale', function (): void {
    config(['cashier.webhook.secret' => 'whsec_test']);

    $this->admin->organization->forceFill(['stripe_id' => 'cus_test_123'])->save();

    $this->actingAs($this->admin)
        ->get(route('dashboard', ['season' => Season::current()->label]))
        ->assertOk();

    $versionKey = sprintf('tenant:cache:version:%s', $this->admin->organization_id);

    expect(Cache::has($versionKey))->toBeTrue();

    // Stripe signatures embed the signing time; Cashier's tolerance (300s
    // by default) rejects replays. The signature itself is valid, but the
    // timestamp sits an hour in the past (time() is the real wall clock —
    // the test's frozen Carbon clock does not affect it)...
    $payload = json_encode([
        'id' => 'evt_stale',
        'type' => 'customer.subscription.updated',
        'data' => [
            'object' => [
                'id' => 'sub_stale',
                'customer' => 'cus_test_123',
                'status' => 'active',
                'items' => [
                    'data' => [
                        [
                            'id' => 'si_stale',
                            'price' => ['id' => 'price_test', 'product' => 'prod_test'],
                            'quantity' => 1,
                        ],
                    ],
                ],
                'metadata' => ['name' => 'pro'],
            ],
        ],
    ]);

    expect($payload)->toBeString();

    $this->call(
        'POST',
        route('cashier.webhook'),
        [],
        [],
        [],
        $this->transformHeadersToServerVars([
            'CONTENT_TYPE' => 'application/json',
            'Stripe-Signature' => stripeSignature($payload, 'whsec_test', time() - 3600),
        ]),
        $payload,
    )->assertForbidden();

    // Nothing processed: the version token never rotated...
    expect(Cache::has($versionKey))->toBeTrue();
});

it('restores pro limits when a signature-verified subscription becomes active', function (): void {
    config(['cashier.webhook.secret' => 'whsec_test']);

    $this->admin->organization->forceFill([
        'trial_ends_at' => now()->subDay(),
        'stripe_id' => 'cus_test_123',
    ])->save();

    // Seed the free-plan summary (expired trial, no subscription)...
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('auth.subscription.plan', 'free'));

    // A subscription update webhook for this organization's customer,
    // signed exactly as Stripe signs it...
    $payload = json_encode([
        'id' => 'evt_signed_access',
        'type' => 'customer.subscription.updated',
        'data' => [
            'object' => [
                'id' => 'sub_signed_access',
                'customer' => 'cus_test_123',
                'status' => 'active',
                'items' => [
                    'data' => [
                        [
                            'id' => 'si_signed_access',
                            'price' => ['id' => 'price_test', 'product' => 'prod_test'],
                            'quantity' => 1,
                        ],
                    ],
                ],
                'metadata' => ['name' => 'pro'],
            ],
        ],
    ]);

    expect($payload)->toBeString();

    $this->call(
        'POST',
        route('cashier.webhook'),
        [],
        [],
        [],
        $this->transformHeadersToServerVars([
            'CONTENT_TYPE' => 'application/json',
            'Stripe-Signature' => stripeSignature($payload, 'whsec_test'),
        ]),
        $payload,
    )->assertOk();

    // The acting user instance persists across test requests, and its
    // cached organization still carries the pre-webhook subscriptions
    // relation. Real requests hydrate the user per request, so drop the
    // stale relation and let the recomputed summary query fresh...
    $this->admin->unsetRelation('organization');

    // The webhook stored the subscription and flushed the cache: the
    // recomputed summary reports the pro plan...
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('auth.subscription.plan', 'pro'));
});
