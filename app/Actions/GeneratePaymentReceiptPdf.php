<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Payment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

final readonly class GeneratePaymentReceiptPdf
{
    public function __construct(private GetPaymentReceipt $receipt) {}

    /**
     * The path where a payment's receipt PDF is stored, shared with the
     * controller so downloads and regeneration checks agree.
     */
    public static function pdfPath(int $paymentId): string
    {
        return sprintf('documents/payments/%s/payment-receipt.pdf', $paymentId);
    }

    /**
     * Render the receipt document and convert it to PDF through
     * Gotenberg, storing the result on the default disk.
     *
     * @return string The stored PDF path
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    public function handle(Payment $payment): string
    {
        $html = view('documents.payment-receipt', $this->receipt->handle($payment))->render();

        $response = Http::asMultipart()
            ->attach('files', $html, 'index.html')
            ->timeout(30)
            ->post($this->endpoint(), ['preferCssPageSize' => 'true']);

        $response->throw();

        $path = self::pdfPath($payment->id);
        Storage::disk((string) config('filesystems.default'))->put($path, $response->body());

        return $path;
    }

    private function endpoint(): string
    {
        return sprintf('%s/forms/chromium/convert/html', mb_rtrim((string) config('services.gotenberg.url'), '/'));
    }
}
