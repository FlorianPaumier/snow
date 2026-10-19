<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Payment;
use App\Support\StripeConnect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Stripe\Checkout\Session;

final readonly class StartConnectedCheckout
{
    public function __construct(private StripeConnect $stripe, private SyncConnectedPayment $sync) {}

    public function handle(Payment $payment): string
    {
        $payment->refresh();
        throw_if($payment->paid || $payment->amount < 50, ValidationException::withMessages(['payment' => __('connect.unavailable')]));
        $accountId = $payment->organization->stripe_connect_id;
        throw_if($accountId === null || ! $this->stripe->ready($this->stripe->account($accountId)), ValidationException::withMessages(['payment' => __('connect.not_ready')]));
        if ($payment->stripe_session_id !== null) {
            $this->sync->handle($payment);
        }

        // Persist the attempt before calling Stripe so retries after a timeout reuse its idempotency key.
        DB::transaction(function () use ($payment): void {
            $contract = Contract::query()->withoutGlobalScopes()->where('organization_id', $payment->organization_id)->lockForUpdate()->findOrFail($payment->contract_id);
            $fresh = Payment::query()->withoutGlobalScopes()->where('organization_id', $payment->organization_id)->lockForUpdate()->findOrFail($payment->id);
            $client = Client::query()->withoutGlobalScopes()->where('organization_id', $fresh->organization_id)->findOrFail($contract->client_id);
            throw_if($fresh->paid || $fresh->amount < 50 || $client->isAnonymized(), ValidationException::withMessages(['payment' => __('connect.unavailable')]));
            if ($fresh->stripe_checkout_key === null || in_array($fresh->stripe_status, ['expired', 'failed'], true)) {
                $fresh->forceFill(['stripe_checkout_started_at' => now(), 'stripe_checkout_key' => (string) Str::uuid(), 'stripe_session_id' => null, 'stripe_status' => 'open'])->save();
            }
        });
        $session = DB::transaction(function () use ($payment, $accountId): Session {
            $fresh = Payment::query()->withoutGlobalScopes()->where('organization_id', $payment->organization_id)->lockForUpdate()->findOrFail($payment->id);
            throw_if($fresh->paid || $fresh->stripe_checkout_key === null, ValidationException::withMessages(['payment' => __('connect.unavailable')]));
            $session = $fresh->stripe_session_id !== null
                ? $this->stripe->checkout($fresh->stripe_session_id, $accountId)
                : $this->stripe->createCheckout($fresh, $accountId);
            $fresh->forceFill(['stripe_session_id' => $session->id])->save();

            return $session;
        });
        throw_unless($session->status === 'open' && is_string($session->url), ValidationException::withMessages(['payment' => __('connect.processing')]));

        return $session->url;
    }
}
