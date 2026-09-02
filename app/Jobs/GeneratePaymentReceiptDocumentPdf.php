<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\GeneratePaymentReceiptPdf;
use App\Models\Payment;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class GeneratePaymentReceiptDocumentPdf implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 30];

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
}
