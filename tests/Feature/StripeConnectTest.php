<?php

declare(strict_types=1);

use App\Actions\CloseConnectedCheckout;
use App\Actions\ExportSeasonPayments;
use App\Actions\GetContractDocument;
use App\Actions\GetRevenueEvolution;
use App\Actions\GetSeasonRevenueRecap;
use App\Actions\GetSeasonSummary;
use App\Actions\SyncConnectedPayment;
use App\Models\ContractService;
use App\Models\Payment;
use App\Models\User;
use App\Support\StripeConnect;
use App\ValueObjects\Season;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;

it('offers independent Stripe merchant onboarding only to admins', function (): void {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->get('/settings/payments')->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->component('payments/settings')->where('account', null));
    $worker = User::factory()->worker()->forOrganization($admin->organization)->create();
    $this->actingAs($worker)->get('/settings/payments')->assertForbidden();
    $this->post('/settings/payments')->assertForbidden();
});

it('requires a valid signed customer link without treating a return visit as settlement', function (): void {
    $payment = Payment::factory()->create(['paid' => false]);
    $url = URL::temporarySignedRoute('connected-payments.show', now()->addDay(), ['organization' => $payment->organization_id, 'payment' => $payment->id]);
    $this->get($url)->assertOk();
    expect($payment->refresh()->paid)->toBeFalse();
    $this->get('/pay/'.$payment->organization_id.'/'.$payment->id)->assertForbidden();
});

it('fails closed when the Connect webhook secret is missing or invalid', function (): void {
    config(['services.stripe_connect.webhook_secret' => '']);
    $this->postJson('/stripe/connect/webhook', [])->assertStatus(503);
    config(['services.stripe_connect.webhook_secret' => 'whsec_connect_test']);
    $this->postJson('/stripe/connect/webhook', [])->assertStatus(400);
});

beforeEach(function (): void {
    config(['services.stripe_connect.key' => 'rk_test_connect_fixture', 'services.stripe_connect.webhook_secret' => 'whsec_connect_test', 'services.stripe_connect.livemode' => false]);
    $this->previousStripeHttpClient = ApiRequestor::httpClient();
});

afterEach(function (): void {
    ApiRequestor::setHttpClient($this->previousStripeHttpClient);
});

function fakeConnectStripe(Closure $respond): void
{
    $client = Mockery::mock(ClientInterface::class);
    $client->shouldReceive('request')->andReturnUsing(function ($method, $url, $headers, $params) use ($respond): array {
        $data = $respond($method, parse_url($url, PHP_URL_PATH), $headers, $params);

        return [json_encode($data, JSON_THROW_ON_ERROR), 200, []];
    });
    ApiRequestor::setHttpClient($client);
}

function connectAccount(array $overrides = []): array
{
    return array_replace_recursive(['id' => 'acct_operator', 'object' => 'v2.core.account', 'livemode' => false, 'dashboard' => 'full',
        'defaults' => ['responsibilities' => ['fees_collector' => 'stripe', 'losses_collector' => 'stripe']],
        'configuration' => ['merchant' => ['capabilities' => ['card_payments' => ['status' => 'active']]]]], $overrides);
}

function connectedInstallment(): Payment
{
    $payment = Payment::factory()->create(['amount' => 25000]);
    $payment->organization->forceFill(['stripe_connect_id' => 'acct_operator'])->save();
    $payment->forceFill(['stripe_checkout_key' => (string) Str::uuid(), 'stripe_session_id' => 'cs_installment', 'stripe_status' => 'open'])->save();

    return $payment;
}

function connectSession(Payment $payment, array $overrides = []): array
{
    return array_replace_recursive(['id' => $payment->stripe_session_id ?? 'cs_installment', 'object' => 'checkout.session',
        'mode' => 'payment', 'status' => 'complete', 'payment_status' => 'paid', 'currency' => 'cad', 'livemode' => false,
        'amount_total' => $payment->amount, 'url' => 'https://checkout.stripe.com/c/pay/cs_installment',
        'metadata' => ['payment_id' => (string) $payment->id, 'organization_id' => $payment->organization_id, 'checkout_key' => $payment->stripe_checkout_key],
        'payment_intent' => ['id' => 'pi_installment', 'object' => 'payment_intent', 'status' => 'succeeded', 'currency' => 'cad', 'amount_received' => $payment->amount,
            'latest_charge' => ['id' => 'ch_installment', 'object' => 'charge', 'created' => now()->timestamp, 'amount' => $payment->amount, 'currency' => 'cad', 'paid' => true, 'amount_refunded' => 0, 'disputed' => false]]], $overrides);
}

function postConnectEvent(string $type, array $object, string $account = 'acct_operator', bool $live = false): TestResponse
{
    $payload = json_encode(['id' => 'evt_installment', 'object' => 'event', 'type' => $type, 'account' => $account, 'livemode' => $live, 'data' => ['object' => $object]], JSON_THROW_ON_ERROR);
    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_connect_test');

    return test()->call('POST', '/stripe/connect/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => 't='.$timestamp.',v1='.$signature], $payload);
}

it('creates an independent account with Stripe collecting fees and losses and reuses it for onboarding', function (): void {
    $admin = User::factory()->admin()->create();
    $creates = 0;
    fakeConnectStripe(function ($method, $path, $headers, array $params) use (&$creates): array {
        if ($path === '/v2/core/accounts') {
            $creates++;
            expect($params['dashboard'])->toBe('full')
                ->and($params['defaults']['responsibilities'])->toBe(['fees_collector' => 'stripe', 'losses_collector' => 'stripe'])
                ->and($params['identity']['country'])->toBe('CA')
                ->and($params['configuration']['merchant']['capabilities']['card_payments']['requested'])->toBeTrue();

            return connectAccount();
        }

        expect($path)->toBe('/v2/core/account_links')->and($params['account'])->toBe('acct_operator')
            ->and($params['use_case']['type'])->toBe('account_onboarding');

        return ['object' => 'v2.core.account_link', 'url' => 'https://connect.stripe.com/setup/test'];
    });
    $this->actingAs($admin)->post('/settings/payments')->assertRedirect('https://connect.stripe.com/setup/test');
    $this->post('/settings/payments')->assertRedirect('https://connect.stripe.com/setup/test');
    expect($creates)->toBe(1)->and($admin->organization->refresh()->stripe_connect_id)->toBe('acct_operator');
});

it('creates a direct CAD checkout without platform fees or transfers and reuses its session', function (): void {
    $payment = Payment::factory()->create(['amount' => 25000]);
    $payment->organization->forceFill(['stripe_connect_id' => 'acct_operator'])->save();
    $creates = 0;
    fakeConnectStripe(function ($method, $path, $headers, array $params) use ($payment, &$creates): array {
        if (str_starts_with($path, '/v2/core/accounts/')) {
            return connectAccount();
        }

        expect($headers)->toContain('Stripe-Account: acct_operator');
        if ($method === 'post') {
            $creates++;
            expect($params['line_items'][0]['price_data']['unit_amount'])->toBe(25000)
                ->and($params['line_items'][0]['price_data']['currency'])->toBe('cad')
                ->and($params['adaptive_pricing']['enabled'])->toBe('false')
                ->and($params)->not->toHaveKey('payment_method_types')
                ->and($params['payment_intent_data'])->not->toHaveKeys(['application_fee_amount', 'transfer_data', 'on_behalf_of'])
                ->and($params['integration_identifier'])->toMatch('/^frostline_installment_[a-z]{8}$/');
        }

        return connectSession($payment->refresh(), ['status' => 'open', 'payment_status' => 'unpaid', 'payment_intent' => null]);
    });
    $url = $payment->paymentUrl();
    $this->post($url)->assertRedirect('https://checkout.stripe.com/c/pay/cs_installment');
    $this->post($url)->assertRedirect('https://checkout.stripe.com/c/pay/cs_installment');
    expect($creates)->toBe(1)->and($payment->refresh()->stripe_session_id)->toBe('cs_installment')->and($payment->paid)->toBeFalse();
});

it('rejects unsafe merchant responsibility configurations before reserving an installment', function (array $account): void {
    $payment = Payment::factory()->create();
    $payment->organization->forceFill(['stripe_connect_id' => 'acct_operator'])->save();
    fakeConnectStripe(fn (): array => connectAccount($account));
    $this->post($payment->paymentUrl())->assertSessionHasErrors('payment');
    expect($payment->refresh()->stripe_checkout_key)->toBeNull();
})->with([
    [['dashboard' => 'express']],
    [['defaults' => ['responsibilities' => ['losses_collector' => 'application']]]],
    [['defaults' => ['responsibilities' => ['fees_collector' => 'application']]]],
    [['configuration' => ['merchant' => ['capabilities' => ['card_payments' => ['status' => 'restricted']]]]]],
]);

it('settles only verified paid sessions and handles duplicates and out of order notifications', function (): void {
    $payment = connectedInstallment();
    $paid = false;
    // Capture by reference to change Stripe's authoritative response between events.
    fakeConnectStripe(function () use ($payment, &$paid): array {
        return connectSession($payment, ['payment_status' => $paid ? 'paid' : 'unpaid']);
    });
    postConnectEvent('checkout.session.completed', ['id' => 'cs_installment'])->assertOk();
    expect($payment->refresh()->paid)->toBeFalse()->and($payment->stripe_status)->toBe('processing');
    $paid = true;
    postConnectEvent('checkout.session.async_payment_succeeded', ['id' => 'cs_installment'])->assertOk();
    $paidAt = $payment->refresh()->paid_at;
    postConnectEvent('checkout.session.completed', ['id' => 'cs_installment'])->assertOk();
    postConnectEvent('checkout.session.expired', ['id' => 'cs_installment'])->assertOk();
    expect($payment->refresh()->paid)->toBeTrue()->and($payment->stripe_payment_intent_id)->toBe('pi_installment')
        ->and($payment->paid_at->eq($paidAt))->toBeTrue()->and($payment->collectedAmount())->toBe(25000);
});

it('rejects mismatched provider amounts currency merchant metadata and transaction details', function (array $overrides): void {
    $payment = connectedInstallment();
    fakeConnectStripe(fn (): array => connectSession($payment, $overrides));
    postConnectEvent('checkout.session.completed', ['id' => 'cs_installment'])->assertStatus(422);
    expect($payment->refresh()->paid)->toBeFalse();
})->with([
    [['amount_total' => 1]], [['currency' => 'usd']], [['metadata' => ['organization_id' => 'another-tenant']]],
    [['metadata' => ['payment_id' => '9999']]], [['metadata' => ['checkout_key' => 'old-key']]],
    [['payment_intent' => ['amount_received' => 1]]], [['payment_intent' => ['latest_charge' => ['currency' => 'usd']]]],
]);

it('ignores unrelated merchants and sessions and rejects events from the other Stripe mode', function (): void {
    $payment = connectedInstallment();
    $client = Mockery::mock(ClientInterface::class);
    $client->shouldNotReceive('request');
    ApiRequestor::setHttpClient($client);
    postConnectEvent('checkout.session.completed', ['id' => 'cs_installment'], 'acct_other')->assertOk();
    postConnectEvent('checkout.session.completed', ['id' => 'cs_other'])->assertOk();
    postConnectEvent('checkout.session.completed', ['id' => 'cs_installment'], live: true)->assertStatus(400);

    expect($payment->refresh()->paid)->toBeFalse();
});

it('prevents offline collection and contract deletion during online checkout and unmark after settlement', function (): void {
    $payment = connectedInstallment();
    $admin = User::factory()->admin()->forOrganization($payment->organization)->create();
    $this->actingAs($admin)->post('/payments/'.$payment->id.'/mark-paid', ['method' => 'cash'])->assertSessionHasErrors('payment');
    $this->delete('/contracts/'.$payment->contract_id)->assertSessionHasErrors('contract');
    $payment->forceFill(['paid' => true, 'stripe_payment_intent_id' => 'pi_installment'])->save();
    $this->post('/payments/'.$payment->id.'/unmark')->assertSessionHasErrors('payment');
    expect($payment->refresh()->paid)->toBeTrue();
});

it('expires an unpaid checkout before allowing an offline collection', function (): void {
    $payment = connectedInstallment();
    $expired = false;
    fakeConnectStripe(function ($method) use ($payment, &$expired): array {
        if ($method === 'post') {
            $expired = true;
        }

        return connectSession($payment, ['status' => $expired ? 'expired' : 'open', 'payment_status' => 'unpaid', 'payment_intent' => null]);
    });
    $admin = User::factory()->admin()->forOrganization($payment->organization)->create();
    $this->actingAs($admin)->delete('/payments/'.$payment->id.'/online')->assertRedirect();
    expect($expired)->toBeTrue()->and($payment->refresh()->stripe_checkout_key)->toBeNull();
    $this->post('/payments/'.$payment->id.'/mark-paid', ['method' => 'cash'])->assertRedirect()->assertSessionHasNoErrors();
    expect($payment->refresh()->paid)->toBeTrue();
});

it('syncs partial and full refunds without reopening collection or issuing a full-payment receipt', function (): void {
    $payment = connectedInstallment();
    $refund = 0;
    fakeConnectStripe(function () use ($payment, &$refund): array {
        return connectSession($payment, ['payment_intent' => ['latest_charge' => ['amount_refunded' => $refund]]]);
    });
    postConnectEvent('checkout.session.completed', ['id' => 'cs_installment'])->assertOk();
    $refund = 5000;
    postConnectEvent('charge.refunded', ['id' => 'ch_installment', 'payment_intent' => 'pi_installment'])->assertOk();
    expect($payment->refresh()->paid)->toBeTrue()->and($payment->collectedAmount())->toBe(20000)
        ->and($payment->stripe_status)->toBe('partially_refunded')->and($payment->receiptAvailable())->toBeFalse();
    $admin = User::factory()->admin()->forOrganization($payment->organization)->create();
    $this->actingAs($admin)->get('/payments/'.$payment->id.'/receipt/print')->assertNotFound();
    $this->get('/contracts/'.$payment->contract_id)->assertInertia(fn (Assert $page): Assert => $page->where('contract.collected', 20000));
    $refund = 25000;
    postConnectEvent('charge.refunded', ['id' => 'ch_installment', 'payment_intent' => 'pi_installment'])->assertOk();
    expect($payment->refresh()->collectedAmount())->toBe(0)->and($payment->stripe_status)->toBe('refunded');
    $this->post($payment->paymentUrl())->assertSessionHasErrors('payment');
});

it('blocks concurrent PDF generation while reconciling or closing a checkout', function (bool $closing): void {
    if (DB::connection()->getDriverName() !== 'pgsql') {
        $this->markTestSkipped('Requires PostgreSQL row locks.');
    }

    $observer = DB::connection();
    $original = DB::getDefaultConnection();
    config(['database.connections.stripe_lock_test' => [...$observer->getConfig(), 'name' => 'stripe_lock_test']]);
    DB::setDefaultConnection('stripe_lock_test');
    $payment = connectedInstallment();
    $organization = $payment->organization;
    $blocked = false;
    fakeConnectStripe(function () use ($payment, $observer, &$blocked): array {
        try {
            $observer->transaction(function () use ($observer, $payment): void {
                $observer->select('SELECT id FROM contracts WHERE id = ? FOR UPDATE NOWAIT', [$payment->contract_id]);
                throw new RuntimeException('Concurrent PDF generation was not blocked.');
            });
        } catch (Illuminate\Database\QueryException $exception) {
            expect($exception->errorInfo[0])->toBe('55P03');
            $blocked = true;
        }

        return connectSession($payment);
    });

    try {
        resolve($closing ? CloseConnectedCheckout::class : SyncConnectedPayment::class)->handle($payment);
        expect($blocked)->toBeTrue()->and($payment->refresh()->paid)->toBeTrue();
    } finally {
        $organization->delete();
        DB::setDefaultConnection($original);
        DB::purge('stripe_lock_test');
    }
})->with(['reconciliation' => false, 'checkout closure' => true]);

it('reconciles missed webhooks and dispute outcomes from the merchant account', function (): void {
    $payment = connectedInstallment();
    $disputeStatus = 'under_review';
    fakeConnectStripe(function ($method, $path) use ($payment, &$disputeStatus): array {
        if ($path === '/v1/disputes') {
            return ['object' => 'list', 'url' => '/v1/disputes', 'has_more' => false, 'data' => [['id' => 'dp_installment', 'object' => 'dispute', 'amount' => 25000, 'status' => $disputeStatus]]];
        }

        return connectSession($payment, ['payment_intent' => ['latest_charge' => ['disputed' => true]]]);
    });
    $this->artisan('payments:reconcile-stripe')->assertSuccessful();
    expect($payment->refresh()->stripe_status)->toBe('disputed')->and($payment->collectedAmount())->toBe(0);
    $disputeStatus = 'won';
    postConnectEvent('charge.dispute.closed', ['id' => 'dp_installment', 'payment_intent' => 'pi_installment'])->assertOk();
    expect($payment->refresh()->stripe_status)->toBe('paid')->and($payment->collectedAmount())->toBe(25000);
});

it('uses the signed merchant scope even for a visitor logged into a different organization and hides anonymized clients', function (): void {
    $payment = connectedInstallment();
    $other = User::factory()->admin()->create();
    $this->actingAs($other)->get($payment->paymentUrl())->assertOk();
    $this->patch('/payments/'.$payment->id.'/online')->assertNotFound();
    fakeConnectStripe(fn ($method, $path): array => str_starts_with((string) $path, '/v2/') ? connectAccount() : connectSession($payment, ['status' => 'open', 'payment_status' => 'unpaid', 'payment_intent' => null]));
    $this->post($payment->paymentUrl())->assertRedirect('https://checkout.stripe.com/c/pay/cs_installment');
    $payment->contract()->withoutGlobalScopes()->first()->client()->withoutGlobalScopes()->first()->forceFill(['anonymized_at' => now()])->save();
    $this->get($payment->paymentUrl())->assertNotFound();
});

it('recovers a session whose creation succeeded before the local save failed', function (): void {
    $payment = connectedInstallment();
    $payment->forceFill(['stripe_session_id' => null])->save();
    $creates = 0;
    fakeConnectStripe(function ($method, $path, $headers, $params) use ($payment, &$creates): array {
        if ($method === 'post') {
            $creates++;
            expect($headers)->toContain('Idempotency-Key: installment:'.$payment->stripe_checkout_key);
        }

        return connectSession($payment);
    });
    $this->artisan('payments:reconcile-stripe')->assertSuccessful();
    expect($creates)->toBe(1)->and($payment->refresh()->paid)->toBeTrue()->and($payment->stripe_session_id)->toBe('cs_installment');
});

it('never creates a second checkout when an uncertain attempt outlives Stripe idempotency retention', function (): void {
    $payment = connectedInstallment();
    $payment->forceFill(['stripe_session_id' => null, 'stripe_checkout_started_at' => now()->subDays(2)])->save();
    fakeConnectStripe(function ($method, $path) use ($payment): array {
        expect($method)->toBe('get');
        if ($path === '/v1/checkout/sessions') {
            return ['object' => 'list', 'url' => '/v1/checkout/sessions', 'has_more' => false, 'data' => [connectSession($payment)]];
        }

        return connectSession($payment);
    });
    $this->artisan('payments:reconcile-stripe')->assertSuccessful();
    expect($payment->refresh()->paid)->toBeTrue();
});

it('keeps net collections consistent across reports exports client pages and contract documents', function (): void {
    $payment = connectedInstallment();
    $admin = User::factory()->admin()->forOrganization($payment->organization)->create();
    $this->actingAs($admin);
    $payment->forceFill(['paid' => true, 'stripe_payment_intent_id' => 'pi_installment', 'stripe_status' => 'partially_refunded', 'stripe_reversed_amount' => 5000])->save();
    $contract = $payment->contract;
    ContractService::factory()->forContract($contract)->create(['amount' => 25000]);
    expect(resolve(GetSeasonSummary::class)->handle(Season::fromYears(now()->year))['collected'])->toBe(20000)
        ->and(resolve(GetRevenueEvolution::class)->handle()->first()['collected'])->toBe(20000)
        ->and(resolve(GetSeasonRevenueRecap::class)->handle()->first()['collected'])->toBe(20000)
        ->and(resolve(GetContractDocument::class)->handle($contract)['collected'])->toBe(20000);
    $this->get('/contracts')->assertInertia(fn (Assert $page): Assert => $page->where('contracts.data.0.collected', 20000));
    $this->get('/clients/'.$contract->client_id)->assertInertia(fn (Assert $page): Assert => $page->where('client.contracts.0.collected', 20000));
    $rows = iterator_to_array(resolve(ExportSeasonPayments::class)->handle());
    $row = array_combine(ExportSeasonPayments::headers(), $rows[0]);
    expect($row['Montant annulé/remboursé (CAD)'])->toBe('50.00')->and($row['Méthode'])->toBe('Stripe: partially_refunded');
});

it('suppresses reminders while Stripe is processing a payment and after a refund', function (): void {
    $payment = connectedInstallment();
    $payment->forceFill(['stripe_status' => 'processing', 'due_date' => today()])->save();
    $admin = User::factory()->admin()->forOrganization($payment->organization)->create();
    $this->actingAs($admin)->post('/payments/'.$payment->id.'/send-reminder')->assertSessionHasErrors('payment');
    $payment->forceFill(['paid' => true, 'stripe_status' => 'refunded', 'stripe_reversed_amount' => 25000])->save();
    $this->post('/payments/'.$payment->id.'/send-reminder')->assertSessionHasErrors('payment');
});

it('releases a definitively failed completed checkout for another collection', function (): void {
    $payment = connectedInstallment();
    fakeConnectStripe(fn (): array => connectSession($payment, ['payment_status' => 'unpaid', 'payment_intent' => ['status' => 'requires_payment_method', 'amount_received' => 0, 'latest_charge' => ['paid' => false]]]));
    postConnectEvent('checkout.session.async_payment_failed', ['id' => 'cs_installment'])->assertOk();
    expect($payment->refresh()->stripe_status)->toBe('failed')->and($payment->paid)->toBeFalse();
    $admin = User::factory()->admin()->forOrganization($payment->organization)->create();
    $this->actingAs($admin)->delete('/payments/'.$payment->id.'/online')->assertRedirect();
    expect($payment->refresh()->stripe_checkout_key)->toBeNull();
    $this->delete('/payments/'.$payment->id.'/online')->assertRedirect();
    expect($payment->refresh()->stripe_checkout_key)->toBeNull();
});

it('rejects installments below the CAD minimum without locking offline collection', function (): void {
    $payment = Payment::factory()->create(['amount' => 1]);
    $payment->organization->forceFill(['stripe_connect_id' => 'acct_operator'])->save();
    fakeConnectStripe(fn (): array => connectAccount());
    $this->post($payment->paymentUrl())->assertSessionHasErrors('payment');
    expect($payment->refresh()->stripe_checkout_key)->toBeNull();
});

it('disables checkout when its signing secret has not been configured', function (): void {
    $payment = connectedInstallment();
    config(['services.stripe_connect.webhook_secret' => '']);
    $this->post($payment->paymentUrl())->assertStatus(503);
});

it('accounts for a partial refund and a separate partial dispute together', function (): void {
    $payment = connectedInstallment();
    fakeConnectStripe(function ($method, $path) use ($payment): array {
        if ($path === '/v1/disputes') {
            return ['object' => 'list', 'url' => '/v1/disputes', 'has_more' => false, 'data' => [['id' => 'dp_partial', 'object' => 'dispute', 'amount' => 10000, 'status' => 'under_review']]];
        }

        return connectSession($payment, ['payment_intent' => ['latest_charge' => ['disputed' => true, 'amount_refunded' => 5000]]]);
    });
    $this->artisan('payments:reconcile-stripe')->assertSuccessful();
    expect($payment->refresh()->stripe_reversed_amount)->toBe(15000)->and($payment->collectedAmount())->toBe(10000);
});

it('retries the identical checkout payload after a timeout and clock advance', function (): void {
    $payment = connectedInstallment();
    $payment->forceFill(['stripe_session_id' => null, 'stripe_checkout_started_at' => now()])->save();
    $payloads = [];
    fakeConnectStripe(function ($method, $path, $headers, $params) use ($payment, &$payloads): array {
        $payloads[] = $params;
        throw_if(count($payloads) === 1, RuntimeException::class, 'Response lost');

        return connectSession($payment);
    });
    $stripe = resolve(StripeConnect::class);
    expect(fn () => $stripe->createCheckout($payment, 'acct_operator'))->toThrow(RuntimeException::class);
    $this->travel(10)->minutes();
    $stripe->createCheckout($payment->fresh(), 'acct_operator');
    expect($payloads)->toHaveCount(2)->and($payloads[1])->toBe($payloads[0]);
});

it('reconciles pending and recent settlements while skipping historical settlements', function (): void {
    $old = connectedInstallment();
    $old->forceFill(['paid' => true, 'paid_at' => now()->subDays(31)])->save();
    $recent = Payment::factory()->forContract($old->contract)->secondInstallment()->create(['amount' => 25000]);
    $recent->forceFill(['stripe_checkout_key' => (string) Str::uuid()])->save();
    $recent->forceFill(['stripe_session_id' => 'cs_recent', 'stripe_payment_intent_id' => 'pi_recent', 'paid' => true, 'paid_at' => now()->subDay()])->save();
    $pending = Payment::factory()->forContract($old->contract)->balance()->create(['amount' => 25000]);
    $pending->forceFill(['stripe_checkout_key' => (string) Str::uuid()])->save();
    $pending->forceFill(['stripe_session_id' => 'cs_pending', 'stripe_checkout_started_at' => now()->subDays(90)])->save();
    $expired = Payment::factory()->forContract($old->contract)->deposit()->create(['amount' => 25000]);
    $expired->forceFill(['stripe_checkout_key' => (string) Str::uuid(), 'stripe_session_id' => 'cs_expired',
        'stripe_checkout_started_at' => now()->subDays(90), 'stripe_status' => 'expired'])->save();
    $seen = [];
    fakeConnectStripe(function ($method, $path) use ($recent, $pending, &$seen): array {
        $seen[] = $path;

        return str_contains($path, 'cs_recent') ? connectSession($recent, ['payment_intent' => ['id' => 'pi_recent']]) : connectSession($pending);
    });
    $this->artisan('payments:reconcile-stripe')->assertSuccessful();
    expect($seen)->toHaveCount(2)->and($seen)->not->toContain('/v1/checkout/sessions/cs_installment')
        ->and($pending->refresh()->paid)->toBeTrue();
});
