<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetPaymentReceipt;
use App\Models\Payment;
use Illuminate\Http\Response;

final readonly class PrintPaymentReceiptController
{
    /**
     * The printable receipt, rendered as a standalone HTML page the
     * browser can print or save as PDF.
     */
    public function __invoke(Payment $payment, GetPaymentReceipt $receipt): Response
    {
        abort_unless($payment->receiptAvailable(), 404);

        return response()->view('documents.payment-receipt', [
            ...$receipt->handle($payment),
            'toolbar' => true,
        ]);
    }
}
