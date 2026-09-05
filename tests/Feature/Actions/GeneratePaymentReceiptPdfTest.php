<?php

declare(strict_types=1);

use App\Actions\GeneratePaymentReceiptPdf;
use App\Enums\PaymentMethod;
use App\Enums\PaymentSlot;
use App\Jobs\GeneratePaymentReceiptDocumentPdf;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * A contract with one service and a paid deposit.
 */
function receiptPayment(User $user): Payment
{
    $client = Client::factory()->forOrganization($user->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();
    $contract->services()->create(['label' => 'Déneigement entrée', 'amount' => 42500, 'position' => 1]);

    return $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 15000,
        'paid' => true,
        'method' => PaymentMethod::PAYMENT_VIREMENT,
        'paid_at' => now(),
    ]);
}

function fakeReceiptDisk(): FilesystemAdapter
{
    return Storage::fake((string) config('filesystems.default'));
}

function fakeReceiptGotenberg(): void
{
    Http::fake([
        '*/forms/chromium/convert/html' => Http::response('%PDF-1.4 fake bytes', 200, [
            'Content-Type' => 'application/pdf',
        ]),
    ]);
}

it('renders the receipt and stores the Gotenberg PDF', function (): void {
    fakeReceiptDisk();
    fakeReceiptGotenberg();
    $user = User::factory()->create();
    $payment = receiptPayment($user);

    $path = resolve(GeneratePaymentReceiptPdf::class)->handle($payment);

    expect($path)->toBe(GeneratePaymentReceiptPdf::pdfPath($payment->id));

    $disk = Storage::disk((string) config('filesystems.default'));
    $disk->assertExists($path);

    expect($disk->get($path))->toBe('%PDF-1.4 fake bytes');

    Http::assertSent(fn (Request $request): bool => str_contains((string) $request->url(), '/forms/chromium/convert/html'));
});

it('fails when Gotenberg rejects the receipt render', function (): void {
    Http::fake([
        '*/forms/chromium/convert/html' => Http::response('boom', 500),
    ]);
    $user = User::factory()->create();
    $payment = receiptPayment($user);

    resolve(GeneratePaymentReceiptPdf::class)->handle($payment);
})->throws(RequestException::class);

it('resolves the payment from its id and generates the receipt pdf', function (): void {
    $disk = fakeReceiptDisk();
    fakeReceiptGotenberg();
    $user = User::factory()->create();
    $payment = receiptPayment($user);

    new GeneratePaymentReceiptDocumentPdf($payment->id)->handle(resolve(GeneratePaymentReceiptPdf::class));

    $disk->assertExists(GeneratePaymentReceiptPdf::pdfPath($payment->id));
});

it('logs when receipt pdf generation fails after exhausting retries', function (): void {
    $payment = receiptPayment(User::factory()->create());

    Log::shouldReceive('error')
        ->once()
        ->with('Payment receipt PDF generation failed', Mockery::on(fn (array $context): bool => $context === [
            'payment_id' => $payment->id,
            'error' => 'Gotenberg unavailable',
        ]));

    new GeneratePaymentReceiptDocumentPdf($payment->id)
        ->failed(new RuntimeException('Gotenberg unavailable'));
});
