<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Payment;
use Illuminate\Validation\ValidationException;

final readonly class UnmarkPaymentPaid
{
    public function handle(Payment $payment): void
    {
        throw_unless(
            $payment->paid,
            ValidationException::withMessages([
                'payment' => 'Ce versement n\'est pas marqué comme encaissé.',
            ]),
        );

        $payment->update([
            'paid' => false,
            'method' => null,
            'paid_at' => null,
        ]);
    }
}
