<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Service\Checkout\SessionService;
use Stripe\Service\CustomerService;
use Stripe\StripeClient;

beforeEach(function (): void {
    $this->organization = Organization::factory()->create(['name' => 'Billing Org']);
    $this->admin = User::factory()->admin()->forOrganization($this->organization)->create();
});

it('renders the billing page for admins', function (): void {
    $this->actingAs($this->admin)
        ->get(route('billing.index'))
        ->assertOk()
        ->assertInertia(function ($page): void {
            $page->component('billing/index')
                ->where('organization.name', 'Billing Org')
                ->has('plan')
                ->where('plan.key', 'pro')
                ->where('plan.name', 'Pro');
        });
});

it('forbids billing for non-admin users', function (): void {
    $worker = User::factory()->worker()->forOrganization($this->organization)->create();

    $this->actingAs($worker)
        ->get(route('billing.index'))
        ->assertForbidden();
});

it('requires authentication', function (): void {
    $this->get(route('billing.index'))
        ->assertRedirectToRoute('login');
});

it('rejects an unknown plan at checkout', function (): void {
    $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->post(route('billing.checkout'), ['plan' => 'enterprise', 'interval' => 'monthly'])
        ->assertRedirectToRoute('billing.index')
        ->assertSessionHasErrors('plan');
});

it('rejects the free plan at checkout', function (): void {
    // Free is the default state, not something you subscribe to...
    $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->post(route('billing.checkout'), ['plan' => 'free', 'interval' => 'monthly'])
        ->assertRedirectToRoute('billing.index')
        ->assertSessionHasErrors('plan');
});

it('rejects an invalid billing interval at checkout', function (string $interval): void {
    $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->post(route('billing.checkout'), ['plan' => 'pro', 'interval' => $interval])
        ->assertRedirectToRoute('billing.index')
        ->assertSessionHasErrors('interval');
})->with([
    'quarterly' => ['quarterly'],
    'weekly' => ['weekly'],
    'missing' => [''],
]);

it('requires a plan and an interval at checkout', function (): void {
    $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->post(route('billing.checkout'), [])
        ->assertRedirectToRoute('billing.index')
        ->assertSessionHasErrors(['plan', 'interval']);
});

it('redirects to billing with a message when the plan has no price configured', function (): void {
    config()->set('plans.pro.prices.monthly');

    $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->post(route('billing.checkout'), ['plan' => 'pro', 'interval' => 'monthly'])
        ->assertRedirectToRoute('billing.index')
        ->assertSessionHas('status', 'This plan is not configured yet. Please contact support.');
});

it('redirects to the Stripe checkout with tax enabled when the monthly price is configured', function (): void {
    config()->set('plans.pro.prices.monthly', 'price_test_monthly');

    $this->admin->organization->forceFill(['stripe_id' => 'cus_test_123'])->save();

    $checkoutPayload = [];

    bindFakeStripeClient(checkoutUrl: 'https://checkout.stripe.com/test', checkoutPayload: $checkoutPayload);

    $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->post(route('billing.checkout'), ['plan' => 'pro', 'interval' => 'monthly'])
        ->assertRedirect('https://checkout.stripe.com/test');

    // GST/QST is calculated by Stripe Tax at checkout, on a required
    // billing address...
    expect($checkoutPayload['automatic_tax'])->toBe(['enabled' => true])
        ->and($checkoutPayload['billing_address_collection'])->toBe('required')
        ->and($checkoutPayload['line_items'][0]['price'])->toBe('price_test_monthly');
});

it('uses the annual price for annual checkouts', function (): void {
    config()->set('plans.pro.prices.annual', 'price_test_annual');

    $this->admin->organization->forceFill(['stripe_id' => 'cus_test_123'])->save();

    $checkoutPayload = [];

    bindFakeStripeClient(checkoutUrl: 'https://checkout.stripe.com/test', checkoutPayload: $checkoutPayload);

    $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->post(route('billing.checkout'), ['plan' => 'pro', 'interval' => 'annual'])
        ->assertRedirect('https://checkout.stripe.com/test');

    expect($checkoutPayload['line_items'][0]['price'])->toBe('price_test_annual');
});

it('starts the checkout subscription at the end of the remaining signup trial', function (): void {
    config()->set('plans.pro.prices.monthly', 'price_test_monthly');

    $this->organization->forceFill([
        'stripe_id' => 'cus_test_123',
        'trial_ends_at' => now()->addDays(10),
    ])->save();

    $checkoutPayload = [];

    bindFakeStripeClient(checkoutUrl: 'https://checkout.stripe.com/test', checkoutPayload: $checkoutPayload);

    $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->post(route('billing.checkout'), ['plan' => 'pro', 'interval' => 'monthly'])
        ->assertRedirect('https://checkout.stripe.com/test');

    expect($checkoutPayload['subscription_data']['trial_end'])
        ->toEqual($this->organization->refresh()->trial_ends_at->getTimestamp());
});

it('bills immediately when the signup trial has expired', function (): void {
    config()->set('plans.pro.prices.monthly', 'price_test_monthly');

    $this->organization->forceFill([
        'stripe_id' => 'cus_test_123',
        'trial_ends_at' => now()->subDay(),
    ])->save();

    $checkoutPayload = [];

    bindFakeStripeClient(checkoutUrl: 'https://checkout.stripe.com/test', checkoutPayload: $checkoutPayload);

    $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->post(route('billing.checkout'), ['plan' => 'pro', 'interval' => 'monthly'])
        ->assertRedirect('https://checkout.stripe.com/test');

    expect($checkoutPayload['subscription_data'])->not->toHaveKey('trial_end');
});

it('bills immediately when the organization has no signup trial', function (): void {
    config()->set('plans.pro.prices.monthly', 'price_test_monthly');

    $this->organization->forceFill([
        'stripe_id' => 'cus_test_123',
        'trial_ends_at' => null,
    ])->save();

    $checkoutPayload = [];

    bindFakeStripeClient(checkoutUrl: 'https://checkout.stripe.com/test', checkoutPayload: $checkoutPayload);

    $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->post(route('billing.checkout'), ['plan' => 'pro', 'interval' => 'monthly'])
        ->assertRedirect('https://checkout.stripe.com/test');

    expect($checkoutPayload['subscription_data'])->not->toHaveKey('trial_end');
});

it('bills immediately when the organization already has a subscription', function (): void {
    config()->set('plans.pro.prices.monthly', 'price_test_monthly');

    $this->organization->forceFill(['stripe_id' => 'cus_test_123'])->save();

    $this->organization->subscriptions()->create([
        'type' => 'pro',
        'stripe_id' => 'sub_test_123',
        'stripe_status' => 'active',
        'stripe_price' => 'price_test_monthly',
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => null,
    ]);

    $checkoutPayload = [];

    bindFakeStripeClient(checkoutUrl: 'https://checkout.stripe.com/test', checkoutPayload: $checkoutPayload);

    $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->post(route('billing.checkout'), ['plan' => 'pro', 'interval' => 'monthly'])
        ->assertRedirect('https://checkout.stripe.com/test');

    expect($checkoutPayload['subscription_data'])->not->toHaveKey('trial_end');
});

it('redirects to the billing portal for admins', function (): void {
    $this->admin->organization->forceFill(['stripe_id' => 'cus_test_123'])->save();

    bindFakeStripeClient(portalUrl: 'https://billing.stripe.com/test');

    $this->actingAs($this->admin)
        ->put(route('billing.portal'), [])
        ->assertRedirect('https://billing.stripe.com/test');
});

/**
 * Bind a StripeClient double into the container: Cashier::stripe() builds
 * the client through the container, so the double replaces every SDK call.
 * Only the services the covered flows touch are faked; the Checkout and
 * BillingPortal services wrap their session services because Cashier
 * reaches them through two magic-property hops. When $checkoutPayload is
 * given, it receives the params sent to the checkout session create call.
 */
function bindFakeStripeClient(string $checkoutUrl = 'https://checkout.stripe.com/unused', string $portalUrl = 'https://billing.stripe.com/unused', ?array &$checkoutPayload = null): void
{
    $stripeCustomer = Customer::constructFrom(['id' => 'cus_test_123'], []);

    $customerService = Mockery::mock(CustomerService::class);
    $customerService->shouldReceive('retrieve')->with('cus_test_123', ['expand' => []])->andReturn($stripeCustomer);

    $checkoutSession = Session::constructFrom(['url' => $checkoutUrl], []);

    $checkoutSessionService = Mockery::mock(SessionService::class);
    $checkoutSessionService->shouldReceive('create')->andReturnUsing(function (array $payload) use (&$checkoutPayload, $checkoutSession): Session {
        $checkoutPayload = $payload;

        return $checkoutSession;
    });

    $portalSessionService = Mockery::mock(Stripe\Service\BillingPortal\SessionService::class);
    $portalSessionService->shouldReceive('create')->andReturn(['url' => $portalUrl]);

    $checkoutService = new readonly class($checkoutSessionService)
    {
        public function __construct(private object $sessionService) {}

        public function __get(string $name): mixed
        {
            return $name === 'sessions' ? $this->sessionService : null;
        }
    };

    $portalService = new readonly class($portalSessionService)
    {
        public function __construct(private object $sessionService) {}

        public function __get(string $name): mixed
        {
            return $name === 'sessions' ? $this->sessionService : null;
        }
    };

    $client = new class($customerService, $checkoutService, $portalService) extends StripeClient
    {
        public function __construct(
            private readonly object $customerService,
            private readonly object $checkoutService,
            private readonly object $portalService,
        ) {
            parent::__construct(['api_key' => 'sk_test_dummy', 'api_base' => 'http://localhost:0']);
        }

        public function __get($name)
        {
            return match ($name) {
                'customers' => $this->customerService,
                'checkout' => $this->checkoutService,
                'billingPortal' => $this->portalService,
                default => parent::__get($name),
            };
        }
    };

    app()->bind(StripeClient::class, fn (): object => $client);
}
