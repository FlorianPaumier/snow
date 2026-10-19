<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class MarkPaymentPaid
{
    public function __construct(private FlushOrganizationCache $flushCache) {}

    public function handle(Payment $payment, PaymentMethod $method): void
    {
        DB::transaction(function () use ($payment, $method): void {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            throw_if($payment->stripe_checkout_key !== null, ValidationException::withMessages(['payment' => __('connect.locked')]));
            throw_if(
                $payment->paid,
                ValidationException::withMessages([
                    'payment' => __('contracts.already_marked'),
                ]),
            );

            $payment->update([
                'paid' => true,
                'method' => $method,
                // The date of the action...
                'paid_at' => Date::now(),
            ]);

        });

        $this->flushCache->handle($payment->organization_id);
    }
}
