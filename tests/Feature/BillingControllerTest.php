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
                ->has('plans', 3);
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

it('rejects an invalid billing interval at checkout', function (string $interval): void {
    $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->post(route('billing.checkout'), ['plan' => 'starter', 'interval' => $interval])
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
    config()->set('plans.starter.prices.monthly');

    $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->post(route('billing.checkout'), ['plan' => 'starter', 'interval' => 'monthly'])
        ->assertRedirectToRoute('billing.index')
        ->assertSessionHas('status', 'This plan is not configured yet. Please contact support.');
});

it('redirects to the Stripe checkout when the plan price is configured', function (): void {
    config()->set('plans.starter.prices.monthly', 'price_test_monthly');

    $this->admin->organization->forceFill(['stripe_id' => 'cus_test_123'])->save();

    bindFakeStripeClient(checkoutUrl: 'https://checkout.stripe.com/test');

    $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->post(route('billing.checkout'), ['plan' => 'starter', 'interval' => 'monthly'])
        ->assertRedirect('https://checkout.stripe.com/test');
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
 * reaches them through two magic-property hops.
 */
function bindFakeStripeClient(string $checkoutUrl = 'https://checkout.stripe.com/unused', string $portalUrl = 'https://billing.stripe.com/unused'): void
{
    $stripeCustomer = Customer::constructFrom(['id' => 'cus_test_123'], []);

    $customerService = Mockery::mock(CustomerService::class);
    $customerService->shouldReceive('retrieve')->with('cus_test_123', ['expand' => []])->andReturn($stripeCustomer);

    $checkoutSession = Session::constructFrom(['url' => $checkoutUrl], []);

    $checkoutSessionService = Mockery::mock(SessionService::class);
    $checkoutSessionService->shouldReceive('create')->andReturn($checkoutSession);

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
