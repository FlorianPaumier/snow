<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GeneratePaymentReceiptPdf;
use App\Models\Payment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class DownloadPaymentReceiptController
{
    /**
     * Stream the stored receipt as a download.
     */
    public function __invoke(Payment $payment): StreamedResponse
    {
        abort_unless($payment->receiptAvailable(), 404);

        $disk = Storage::disk(config()->string('filesystems.default'));
        $path = GeneratePaymentReceiptPdf::pdfPath($payment->id);

        abort_unless($disk->exists($path), 404);

        $payment->loadMissing('contract.client');

        return $disk->download($path, sprintf('Recu-%s-%s-%s.pdf', $payment->contract->season, $payment->contract->client->clientNumber, $payment->slot->value));
    }
}
