<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UnmarkPaymentPaid
{
    public function __construct(
        private DeleteStoredDocument $deleteDocument,
        private FlushOrganizationCache $flushCache,
    ) {}

    public function handle(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            throw_if($payment->stripe_payment_intent_id !== null, ValidationException::withMessages(['payment' => __('connect.locked')]));
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

        });

        // The receipt PDF proves a payment that no longer exists as such;
        // the generate flow short-circuits while it is stored, so delete it
        // to have it rebuilt once the payment is marked paid again...
        $this->deleteDocument->handle(GeneratePaymentReceiptPdf::pdfPath($payment->id));

        $this->flushCache->handle($payment->organization_id);
    }
}
