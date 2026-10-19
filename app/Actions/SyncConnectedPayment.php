<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contract;
use App\Models\Payment;
use App\Support\StripeConnect;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Stripe\Charge;
use Stripe\PaymentIntent;

final readonly class SyncConnectedPayment
{
    public function __construct(private StripeConnect $stripe, private FlushOrganizationCache $flushCache, private DeleteStoredDocument $deleteDocument) {}

    public function handle(Payment $payment): void
    {
        $changed = DB::transaction(function () use ($payment): bool {
            Contract::query()->withoutGlobalScopes()->where('organization_id', $payment->organization_id)->lockForUpdate()->findOrFail($payment->contract_id);
            $payment = Payment::query()->withoutGlobalScopes()->where('organization_id', $payment->organization_id)->lockForUpdate()->findOrFail($payment->id);
            $account = $payment->organization->stripe_connect_id;
            if ($account === null || $payment->stripe_checkout_key === null) {
                return false;
            }

            $sessionId = $payment->stripe_session_id;
            if ($sessionId === null) {
                $session = $this->stripe->createCheckout($payment, $account);
                $sessionId = $session->id;
                $payment->forceFill(['stripe_session_id' => $sessionId])->save();
            }

            $session = $this->stripe->checkout($sessionId, $account);
            throw_unless($session->id === $payment->stripe_session_id
                && $session->livemode === config()->boolean('services.stripe_connect.livemode')
                && $session->mode === 'payment' && $session->currency === 'cad' && $session->amount_total === $payment->amount
                && ($session->metadata['organization_id'] ?? null) === $payment->organization_id
                && ($session->metadata['payment_id'] ?? null) === (string) $payment->id
                && ($session->metadata['checkout_key'] ?? null) === $payment->stripe_checkout_key,
                ValidationException::withMessages(['payment' => __('connect.mismatch')]));
            if ($session->payment_status !== 'paid') {
                if (! $payment->paid) {
                    $intent = $session->payment_intent;
                    $status = match (true) {
                        $session->status === 'expired', $intent instanceof PaymentIntent && $intent->status === 'canceled' => 'expired',
                        $session->status === 'complete' && $intent instanceof PaymentIntent && $intent->status === 'requires_payment_method' => 'failed',
                        $session->status === 'complete' => 'processing',
                        default => 'open',
                    };
                    $payment->forceFill(['stripe_status' => $status])->save();
                }

                return false;
            }

            $intent = $session->payment_intent;
            if (! $intent instanceof PaymentIntent || ! $intent->latest_charge instanceof Charge) {
                throw ValidationException::withMessages(['payment' => __('connect.mismatch')]);
            }

            $charge = $intent->latest_charge;
            throw_unless($intent->status === 'succeeded' && $intent->currency === 'cad' && $intent->amount_received === $payment->amount
                && $charge->paid && $charge->currency === 'cad' && $charge->amount === $payment->amount
                && ($payment->stripe_payment_intent_id === null || $payment->stripe_payment_intent_id === $intent->id)
                && (! $payment->paid || $payment->stripe_payment_intent_id === $intent->id),
                ValidationException::withMessages(['payment' => __('connect.mismatch')]));
            $reversed = $this->stripe->reversedAmount($charge, $account);
            $status = match (true) {
                $charge->disputed && $reversed > $charge->amount_refunded => 'disputed',
                $reversed === $payment->amount => 'refunded',
                $reversed > 0 => 'partially_refunded',
                default => 'paid',
            };
            $payment->forceFill(['paid' => true, 'method' => null, 'paid_at' => $payment->paid_at ?? Date::createFromTimestamp($charge->created),
                'stripe_payment_intent_id' => $intent->id, 'stripe_status' => $status, 'stripe_reversed_amount' => $reversed]);
            if (! $payment->isDirty()) {
                return false;
            }

            $this->deleteDocument->handle(GeneratePaymentReceiptPdf::pdfPath($payment->id));
            $this->deleteDocument->handle(GenerateContractPdf::pdfPath($payment->contract_id));

            $payment->save();

            return true;

        });
        if ($changed) {
            $this->flushCache->handle($payment->organization_id);
        }
    }
}
