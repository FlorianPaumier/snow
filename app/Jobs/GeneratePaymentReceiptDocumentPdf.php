<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\GeneratePaymentReceiptPdf;
use App\Models\Payment;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Backoff([10, 30])]
#[Tries(3)]
final class GeneratePaymentReceiptDocumentPdf implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $paymentId) {}

    /**
     * One PDF per payment: repeated clicks on "generate" must not stack
     * duplicate conversions of the same receipt.
     */
    public function uniqueId(): string
    {
        return (string) $this->paymentId;
    }

    /**
     * Queue jobs run unauthenticated, so the organization scope does not
     * apply and the payment is resolved globally by id.
     */
    public function handle(GeneratePaymentReceiptPdf $generate): void
    {
        $generate->handle(Payment::query()->findOrFail($this->paymentId));
    }

    /**
     * Retries exhausted: the contract page links back to the generate
     * button, so the operator only needs to know the generation died —
     * nothing else is parked or lost.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('Payment receipt PDF generation failed', [
            'payment_id' => $this->paymentId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
