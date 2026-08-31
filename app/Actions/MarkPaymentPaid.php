<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;

final readonly class MarkPaymentPaid
{
    public function handle(Payment $payment, PaymentMethod $method): void
    {
        throw_if(
            $payment->paid,
            ValidationException::withMessages([
                'payment' => 'Ce versement est déjà marqué comme encaissé.',
            ]),
        );

        $payment->update([
            'paid' => true,
            'method' => $method,
            // The date of the action...
            'paid_at' => Date::now(),
        ]);
    }
}
