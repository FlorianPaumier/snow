<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Organization;
use App\Models\Payment;
use LogicException;
use Stripe\Charge;
use Stripe\Checkout\Session;
use Stripe\StripeClient;
use Stripe\V2\Core\Account;

final readonly class StripeConnect
{
    public function __construct(private StripeClient $stripe) {}

    public function account(string $id): Account
    {
        return $this->stripe->v2->core->accounts->retrieve($id, ['include' => ['configuration.merchant', 'defaults', 'requirements']]);
    }

    public function ready(Account $account): bool
    {
        return $account->livemode === config()->boolean('services.stripe_connect.livemode')
            && $account->dashboard === 'full'
            && data_get($account->toArray(), 'defaults.responsibilities.fees_collector') === 'stripe'
            && data_get($account->toArray(), 'defaults.responsibilities.losses_collector') === 'stripe'
            && data_get($account->toArray(), 'configuration.merchant.capabilities.card_payments.status') === 'active';
    }

    public function createAccount(Organization $organization, string $email): Account
    {
        return $this->stripe->v2->core->accounts->create([
            'contact_email' => $email,
            'display_name' => $organization->name,
            'identity' => ['country' => 'CA'],
            'dashboard' => 'full',
            'configuration' => ['merchant' => ['capabilities' => ['card_payments' => ['requested' => true]]]],
            'defaults' => ['currency' => 'cad', 'responsibilities' => ['fees_collector' => 'stripe', 'losses_collector' => 'stripe']],
            'metadata' => ['organization_id' => $organization->id],
        ], ['idempotency_key' => 'connect-account:'.$organization->id]);
    }

    public function onboardingUrl(string $id): string
    {
        $link = $this->stripe->v2->core->accountLinks->create([
            'account' => $id,
            'use_case' => ['type' => 'account_onboarding', 'account_onboarding' => [
                'configurations' => ['merchant'],
                'refresh_url' => route('stripe-connect.show'),
                'return_url' => route('stripe-connect.show'),
            ]],
        ]);

        return $link->url;
    }

    public function createCheckout(Payment $payment, string $account): Session
    {
        throw_if($payment->stripe_checkout_key === null, LogicException::class, 'Checkout requires a persisted idempotency key.');

        if ($payment->stripe_checkout_started_at !== null && $payment->stripe_checkout_started_at->lt(now()->subHours(23))) {
            // Stripe may prune idempotency keys after 24 hours; find the original before issuing another request.
            $sessions = $this->stripe->checkout->sessions->all(['created' => ['gte' => $payment->stripe_checkout_started_at->getTimestamp() - 60], 'limit' => 100], ['stripe_account' => $account]);
            foreach ($sessions->autoPagingIterator() as $session) {
                if (($session->metadata['checkout_key'] ?? null) === $payment->stripe_checkout_key) {
                    return $this->checkout($session->id, $account);
                }
            }
        }

        $metadata = ['payment_id' => (string) $payment->id, 'organization_id' => $payment->organization_id, 'checkout_key' => $payment->stripe_checkout_key];

        return $this->stripe->checkout->sessions->create([
            'mode' => 'payment',
            'adaptive_pricing' => ['enabled' => false],
            'client_reference_id' => (string) $payment->id,
            'metadata' => $metadata,
            'payment_intent_data' => ['metadata' => $metadata],
            'line_items' => [['price_data' => ['currency' => 'cad', 'unit_amount' => $payment->amount,
                'product_data' => ['name' => $payment->slot->label()]], 'quantity' => 1]],
            'success_url' => $payment->paymentUrl($payment->stripe_checkout_started_at),
            'cancel_url' => $payment->paymentUrl($payment->stripe_checkout_started_at),
            'integration_identifier' => 'frostline_installment_'.strtr(mb_substr(hash('sha256', (string) $payment->stripe_checkout_key), 0, 8), '0123456789', 'ghijklmnop'),
        ], ['stripe_account' => $account, 'idempotency_key' => 'installment:'.$payment->stripe_checkout_key]);
    }

    public function checkout(string $session, string $account): Session
    {
        return $this->stripe->checkout->sessions->retrieve($session, ['expand' => ['payment_intent.latest_charge']], ['stripe_account' => $account]);
    }

    public function expireCheckout(string $session, string $account): void
    {
        $this->stripe->checkout->sessions->expire($session, [], ['stripe_account' => $account]);
    }

    public function reversedAmount(Charge $charge, string $account): int
    {
        $reversed = (int) $charge->amount_refunded;
        if ($charge->disputed) {
            $disputes = $this->stripe->disputes->all(['charge' => $charge->id, 'limit' => 100], ['stripe_account' => $account]);
            foreach ($disputes->data as $dispute) {
                if (! in_array($dispute->status, ['won', 'warning_closed'], true)) {
                    $reversed += $dispute->amount;
                }
            }
        }

        return min((int) $charge->amount, $reversed);
    }
}
