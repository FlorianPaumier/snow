<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use App\ValueObjects\Season;
use Inertia\Support\SessionKey;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Service\Checkout\SessionService;
use Stripe\Service\CustomerService;
use Stripe\StripeClient;

beforeEach(function (): void {
    $this->organization = Organization::factory()->expired()->create();
    $this->admin = User::factory()->admin()->forOrganization($this->organization)->create();
});

it('allows read-only organizations to view pages', function (): void {
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk();
});

it('blocks creating clients while read-only', function (): void {
    $response = $this->actingAs($this->admin)
        ->fromRoute('clients.create')
        ->withHeaders(['X-Inertia' => 'true'])
        ->post(route('clients.store'), [
            'type' => 'individual',
            'firstName' => 'Blocked',
            'lastName' => 'Create',
            'email' => 'blocked@example.com',
            'phoneDay' => '418-555-0123',
            'addressStreet' => '123 Rue',
            'addressCity' => 'Québec',
            'addressPostalCode' => 'G1K 0A1',
            'addressCountry' => 'Canada',
        ]);

    $response->assertRedirectToRoute('clients.create')
        ->assertSessionHas('errors');

    expect(Client::query()->withoutGlobalScopes()
        ->where('organization_id', $this->organization->id)
        ->count())->toBe(0);
});

it('blocks creating users while read-only', function (): void {
    $response = $this->actingAs($this->admin)
        ->fromRoute('users.create')
        ->post(route('users.store'), [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
            'role' => 'user',
        ]);

    $response->assertRedirectToRoute('users.create');

    expect(User::query()->withoutGlobalScopes()
        ->where('organization_id', $this->organization->id)
        ->count())->toBe(1); // only the admin...
});

it('blocks creating contracts while read-only', function (): void {
    $client = Client::factory()->forOrganization($this->organization)->create();

    $response = $this->actingAs($this->admin)
        ->fromRoute('contracts.create')
        ->withHeaders(['X-Inertia' => 'true'])
        ->post(route('contracts.store'), [
            'clientId' => $client->id,
            'season' => Season::current()->label,
            'services' => [
                ['label' => 'Déneigement entrée', 'amount' => 35000],
            ],
            'payments' => [
                'deposit' => 15000,
                'first' => 10000,
                'second' => 10000,
                'balance' => 0,
            ],
        ]);

    $response->assertRedirectToRoute('contracts.create')
        ->assertSessionHas('errors');

    expect($client->contracts()->count())->toBe(0);
});

it('allows billing checkout while read-only', function (): void {
    $this->organization->forceFill(['stripe_id' => 'cus_test_123'])->save();
    config(['plans.starter.prices.monthly' => 'price_test_monthly']);

    bindReadOnlyStripeClient();

    $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->post(route('billing.checkout'), ['plan' => 'starter', 'interval' => 'monthly'])
        ->assertRedirect('https://checkout.stripe.com/unused');
});

it('still blocks reads for guests before the middleware runs', function (): void {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('grants the 24h unlock from the billing page', function (): void {
    $response = $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->post(route('billing.unlock'));

    $response->assertRedirectToRoute('billing.index')
        ->assertSessionHas(SessionKey::FLASH_DATA, [
            'toast' => [
                'type' => 'success',
                'message' => __('statuses.unlock_granted'),
            ],
        ]);

    expect($this->organization->fresh()->access_unlock_until?->toIso8601String())
        ->toBe(now()->addHours(24)->toIso8601String());
});

it('refuses the unlock a second time', function (): void {
    $this->organization->forceFill(['access_unlock_until' => now()->addHours(24)])->save();

    $response = $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->withHeaders(['X-Inertia' => 'true'])
        ->post(route('billing.unlock'));

    $response->assertRedirectToRoute('billing.index')
        ->assertSessionHas('errors');

    expect($this->organization->fresh()->access_unlock_until?->toIso8601String())
        ->toBe(now()->addHours(24)->toIso8601String()); // unchanged...
});

it('refuses the unlock after the first one has expired', function (): void {
    $this->organization->forceFill(['access_unlock_until' => now()->subHour()])->save();

    $response = $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->withHeaders(['X-Inertia' => 'true'])
        ->post(route('billing.unlock'));

    $response->assertRedirectToRoute('billing.index')
        ->assertSessionHas('errors');

    expect($this->organization->fresh()->access_unlock_until?->toIso8601String())
        ->toBe(now()->subHour()->toIso8601String()); // unchanged...
});

it('lets an admin unlock full access for 24 hours', function (): void {
    $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->post(route('billing.unlock'))
        ->assertRedirectToRoute('billing.index');

    expect($this->organization->fresh()->access_unlock_until)
        ->not->toBeNull();

    // Mutations work again immediately...
    $this->actingAs($this->admin)
        ->post(route('clients.store'), [
            'type' => 'individual',
            'firstName' => 'Back',
            'lastName' => 'Online',
            'email' => 'back@example.com',
            'phoneDay' => '418-555-0123',
            'addressStreet' => '123 Rue',
            'addressCity' => 'Québec',
            'addressPostalCode' => 'G1K 0A1',
            'addressCountry' => 'Canada',
        ])->assertRedirect();

    expect(Client::query()->withoutGlobalScopes()
        ->where('organization_id', $this->organization->id)
        ->count())->toBe(1);
});

it('re-locks the organization once the unlock expires', function (): void {
    $this->organization->forceFill(['access_unlock_until' => now()->subHour()])->save();

    // The cached verdict was capped at the unlock expiry, so by now it
    // has expired and the next request recomputes read-only...
    $this->travelTo(now()->addHours(2));

    $response = $this->actingAs($this->admin)
        ->fromRoute('clients.create')
        ->withHeaders(['X-Inertia' => 'true'])
        ->post(route('clients.store'), [
            'type' => 'individual',
            'firstName' => 'Locked',
            'lastName' => 'Again',
            'email' => 'locked@example.com',
            'phoneDay' => '418-555-0123',
            'addressStreet' => '123 Rue',
            'addressCity' => 'Québec',
            'addressPostalCode' => 'G1K 0A1',
            'addressCountry' => 'Canada',
        ]);

    $response->assertRedirectToRoute('clients.create')
        ->assertSessionHas('errors');

    expect(Client::query()->withoutGlobalScopes()
        ->where('organization_id', $this->organization->id)
        ->count())->toBe(0);
});

it('forbids non-admins from unlocking', function (): void {
    $worker = User::factory()->forOrganization($this->organization)->create();

    $this->actingAs($worker)
        ->post(route('billing.unlock'))
        ->assertForbidden();
});

/**
 * Local copy of the bindFakeStripeClient() helper from
 * tests/Feature/BillingControllerTest.php: Pest only loads a file's
 * function declarations once that file is part of the run, so the
 * cross-file helper is not defined when this file runs standalone. Bound
 * under a distinct name to avoid a redeclaration clash when both files
 * run together.
 */
function bindReadOnlyStripeClient(string $checkoutUrl = 'https://checkout.stripe.com/unused'): void
{
    $stripeCustomer = Customer::constructFrom(['id' => 'cus_test_123'], []);

    $customerService = Mockery::mock(CustomerService::class);
    $customerService->shouldReceive('retrieve')->with('cus_test_123', ['expand' => []])->andReturn($stripeCustomer);

    $checkoutSession = Session::constructFrom(['url' => $checkoutUrl], []);

    $checkoutSessionService = Mockery::mock(SessionService::class);
    $checkoutSessionService->shouldReceive('create')->andReturn($checkoutSession);

    $checkoutService = new readonly class($checkoutSessionService)
    {
        public function __construct(private object $sessionService) {}

        public function __get(string $name): mixed
        {
            return $name === 'sessions' ? $this->sessionService : null;
        }
    };

    $client = new class($customerService, $checkoutService) extends StripeClient
    {
        public function __construct(
            private readonly object $customerService,
            private readonly object $checkoutService,
        ) {
            parent::__construct(['api_key' => 'sk_test_dummy', 'api_base' => 'http://localhost:0']);
        }

        public function __get($name)
        {
            return match ($name) {
                'customers' => $this->customerService,
                'checkout' => $this->checkoutService,
                default => parent::__get($name),
            };
        }
    };

    app()->bind(StripeClient::class, fn (): object => $client);
}
