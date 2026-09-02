<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GeneratePaymentReceiptPdf;
use App\Jobs\GeneratePaymentReceiptDocumentPdf;
use App\Models\Payment;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class GeneratePaymentReceiptController
{
    /**
     * Ensure a receipt PDF exists for the payment: send the browser to the
     * download when one is already stored, otherwise queue its generation.
     */
    public function __invoke(Payment $payment): SymfonyResponse
    {
        abort_unless($payment->paid, 404);

        if (Storage::disk((string) config('filesystems.default'))->exists(GeneratePaymentReceiptPdf::pdfPath($payment->id))) {
            // Inertia answers a 409 + X-Inertia-Location header so the
            // client performs a full-page navigation and the PDF streams...
            return Inertia::location(route('payments.receipt.download', $payment));
        }

        GeneratePaymentReceiptDocumentPdf::dispatch($payment->id);

        return back()->with('status', __('statuses.document_pdf_generating'));
    }
}
