<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateBillingCheckoutRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class BillingController
{
    public function index(#[CurrentUser] User $user): Response
    {
        $organization = $user->organization;

        return Inertia::render('billing/index', [
            'organization' => [
                'name' => $organization->name,
            ],
            'plan' => $this->plan(),
        ]);
    }

    public function store(CreateBillingCheckoutRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        /** @var array{plan: string, interval: string}  $validated */
        $validated = $request->validated();

        /** @var string|null $priceId */
        $priceId = config(sprintf('plans.%s.prices.%s', $validated['plan'], $validated['interval']));

        if ($priceId === null || $priceId === '') {
            return to_route('billing.index')->with('status', 'This plan is not configured yet. Please contact support.');
        }

        $organization = $user->organization;

        $subscription = $organization->newSubscription($validated['plan'], $priceId);

        // While the organization is still on its signup trial and has no
        // subscription yet, the checkout-created subscription picks up the
        // remaining trial instead of billing immediately...
        $trialEndsAt = $organization->trial_ends_at;

        if ($trialEndsAt !== null && $trialEndsAt->isFuture() && $organization->subscriptions()->doesntExist()) {
            $subscription->trialUntil($trialEndsAt);
        }

        $checkout = $subscription->checkout([
            'success_url' => route('billing.index', absolute: false),
            'cancel_url' => route('billing.index', absolute: false),
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

        return $checkout->redirect();
    }

    public function update(#[CurrentUser] User $user): RedirectResponse
    {
        return $user->organization->redirectToBillingPortal(
            route('billing.index', absolute: false),
        );
    }

    /**
     * The single paid plan offered at checkout.
     *
     * @return array{key: string, name: string, maxUsers: int|null, maxClients: int|null}
     */
    private function plan(): array
    {
        /** @var array{name: string, limits: array{maxUsers: int|null, maxClients: int|null}} $plan */
        $plan = config('plans.pro');

        return [
            'key' => 'pro',
            'name' => $plan['name'],
            'maxUsers' => $plan['limits']['maxUsers'],
            'maxClients' => $plan['limits']['maxClients'],
        ];
    }
}
