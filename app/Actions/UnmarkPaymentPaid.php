<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Payment;
use Illuminate\Support\Facades\Storage;
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

        // The receipt PDF proves a payment that no longer exists as such;
        // the generate flow short-circuits while it is stored, so delete it
        // to have it rebuilt once the payment is marked paid again.
        Storage::disk(config()->string('filesystems.default'))->delete(GeneratePaymentReceiptPdf::pdfPath($payment->id));

        $this->flushCache->handle($payment->organization_id);
    }
}
