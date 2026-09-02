<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Payment;
use Illuminate\Validation\ValidationException;

final readonly class UnmarkPaymentPaid
{
    public function __construct(private FlushOrganizationCache $flushCache) {}

    public function handle(Payment $payment): void
    {
        throw_unless(
            $payment->paid,
            ValidationException::withMessages([
                'payment' => __('contracts.not_marked'),
            ]),
        );

        $payment->update([
            'paid' => false,
            'method' => null,
            'paid_at' => null,
        ]);

        $this->flushCache->handle($payment->organization_id);
    }
}
