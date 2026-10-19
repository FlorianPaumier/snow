<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SyncConnectedPayment;
use App\Models\Organization;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

final readonly class ConnectWebhookController
{
    public function __invoke(Request $request): Response
    {
        $secret = config()->string('services.stripe_connect.webhook_secret');
        abort_if($secret === '', 503);
        try {
            $event = Webhook::constructEvent($request->getContent(), (string) $request->header('Stripe-Signature'), $secret);
        } catch (SignatureVerificationException|UnexpectedValueException) {
            abort(400);
        }

        abort_unless($event->livemode === config()->boolean('services.stripe_connect.livemode'), 400);
        $supported = ['checkout.session.completed', 'checkout.session.async_payment_succeeded', 'checkout.session.async_payment_failed',
            'checkout.session.expired', 'charge.refunded', 'charge.dispute.created', 'charge.dispute.updated', 'charge.dispute.closed'];
        if (! in_array($event->type, $supported, true) || ! is_string($event->account)) {
            return response('Ignored');
        }

        $organization = Organization::query()->where('stripe_connect_id', $event->account)->first();
        if ($organization === null) {
            return response('Ignored');
        }

        $object = $event->data->object;
        $query = Payment::query()->withoutGlobalScopes()->where('organization_id', $organization->id);
        if (str_starts_with($event->type, 'checkout.session.')) {
            $query->where('stripe_session_id', $object->id);
        } else {
            $query->whereNotNull('stripe_payment_intent_id')->where('stripe_payment_intent_id', ($object['payment_intent'] ?? null));
        }

        $payment = $query->first();
        if ($payment !== null) {
            try {
                resolve(SyncConnectedPayment::class)->handle($payment);
            } catch (ValidationException) {
                return response('Payment reconciliation rejected', 422);
            }
        }

        return response('OK');
    }
}
