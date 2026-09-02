<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\FlushOrganizationCache;
use App\Models\Organization;
use Illuminate\Http\Request;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cashier's webhook controller, plus cache invalidation: subscription
 * events (created, updated, deleted, trial changes) are the only way
 * plan status changes after checkout, so every handled webhook rotates
 * the affected organization's cache version — subscription summaries,
 * plan limits and everything else re-seed on the next request.
 *
 * The route registers this as an invokable controller: __invoke wraps
 * Cashier's handleWebhook and adds the flush. Named StripeWebhookController
 * (not Cashier...) to avoid colliding with the parent's imported alias.
 */
final class StripeWebhookController extends WebhookController
{
    public function __construct(private readonly FlushOrganizationCache $flushCache)
    {
        // The parent constructor registers the signature middleware
        // when a webhook secret is configured...
        parent::__construct();
    }

    public function __invoke(Request $request): Response
    {
        $response = $this->handleWebhook($request);

        // Cashier resolves the billable (an Organization here) from the
        // Stripe customer id in the payload...
        $customer = $request->input('data.object.customer');

        /** @var string|null $customer */
        /** @var Organization|null $organization */
        $organization = is_string($customer)
            ? Cashier::findBillable($customer)
            : null;

        if ($organization !== null) {
            /** @var string $organizationId */
            $organizationId = $organization->getKey();

            $this->flushCache->handle($organizationId);
        }

        return $response;
    }
}
