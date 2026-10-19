<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Cashier\Checkout;

final readonly class StartBillingCheckout
{
    public function handle(Organization $organization, string $plan, string $priceId): Checkout
    {
        DB::transaction(function () use ($organization): void {
            $organization->refreshForUpdate();
            $organization->createOrGetStripeCustomer();
        });

        return DB::transaction(function () use ($organization, $plan, $priceId): Checkout {
            $organization->refreshForUpdate();
            throw_if($organization->subscribed($plan), ValidationException::withMessages([
                'plan' => __('billing.already_subscribed'),
            ]));

            $customer = $organization->createOrGetStripeCustomer();
            $stripe = $organization->stripe();
            foreach ($stripe->checkout->sessions->all(['customer' => $customer->id, 'status' => 'open', 'limit' => 100])->autoPagingIterator() as $session) {
                if ($session->mode === 'subscription') {
                    return new Checkout($organization, $session);
                }
            }

            // Stripe is authoritative while subscription webhooks are still in flight.
            foreach ($stripe->subscriptions->all(['customer' => $customer->id, 'status' => 'all', 'limit' => 100])->autoPagingIterator() as $subscription) {
                throw_unless(in_array($subscription->status, ['canceled', 'incomplete_expired'], true), ValidationException::withMessages([
                    'plan' => __('billing.already_subscribed'),
                ]));
            }

            $subscription = $organization->newSubscription($plan, $priceId);

            // While the organization is still on its signup trial and has no
            // subscription yet, the checkout-created subscription picks up the
            // remaining trial instead of billing immediately...
            $trialEndsAt = $organization->trial_ends_at;

            if ($trialEndsAt !== null && $trialEndsAt->isFuture() && $organization->subscriptions()->doesntExist()) {
                $subscription->trialUntil($trialEndsAt);
            }

            return $subscription->checkout([
                'success_url' => route('billing.index'),
                'cancel_url' => route('billing.index'),
                // Stripe Tax computes GST/QST (or the customer's local tax) at
                // checkout: prices are tax-exclusive and the billing address
                // is required to resolve the rate...
                'automatic_tax' => [
                    'enabled' => true,
                ],
                'billing_address_collection' => 'required',
                'customer_update' => [
                    'address' => 'auto',
                ],
            ]);
        });
    }
}
