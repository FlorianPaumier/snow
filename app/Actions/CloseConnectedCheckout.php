<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contract;
use App\Models\Payment;
use App\Support\StripeConnect;
use Illuminate\Support\Facades\DB;

final readonly class CloseConnectedCheckout
{
    public function __construct(private StripeConnect $stripe, private SyncConnectedPayment $sync) {}

    public function handle(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            Contract::query()->where('organization_id', $payment->organization_id)->lockForUpdate()->findOrFail($payment->contract_id);
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $account = $payment->organization->stripe_connect_id;
            if ($payment->paid || $payment->stripe_checkout_key === null || $account === null) {
                return;
            }

            // Recover an uncertain create request with the same key before releasing the offline-payment lock.
            $session = $payment->stripe_session_id === null
                ? $this->stripe->createCheckout($payment, $account)
                : $this->stripe->checkout($payment->stripe_session_id, $account);
            $payment->forceFill(['stripe_session_id' => $session->id])->save();
            if ($session->status === 'open') {
                $this->stripe->expireCheckout($session->id, $account);
            }

            $this->sync->handle($payment);
            if (in_array($payment->refresh()->stripe_status, ['expired', 'failed'], true)) {
                $payment->forceFill(['stripe_checkout_started_at' => null, 'stripe_checkout_key' => null, 'stripe_session_id' => null, 'stripe_status' => null])->save();
            }
        });
    }
}
