<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\StartBillingCheckout;
use App\Http\Requests\CreateBillingCheckoutRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

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

    public function store(CreateBillingCheckoutRequest $request, #[CurrentUser] User $user, StartBillingCheckout $action): HttpResponse
    {
        /** @var array{plan: string, interval: string}  $validated */
        $validated = $request->validated();

        /** @var string|null $priceId */
        $priceId = config(sprintf('plans.%s.prices.%s', $validated['plan'], $validated['interval']));

        if ($priceId === null || $priceId === '') {
            return to_route('billing.index')->withErrors(['plan' => __('billing.not_configured')]);
        }

        return Inertia::location($action->handle($user->organization, $validated['plan'], $priceId)->redirect());
    }

    public function update(#[CurrentUser] User $user): HttpResponse
    {
        return Inertia::location($user->organization->redirectToBillingPortal(
            route('billing.index'),
        ));
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
