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
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

/**
 * A contract with a paid deposit for a named client.
 *
 * @param  array<string, mixed>  $paymentAttributes
 */
function receiptFixture(User $user, array $paymentAttributes = []): Payment
{
    $client = Client::factory()->forOrganization($user->organization)->create([
        'firstName' => 'Jean',
        'lastName' => 'Tremblay',
    ]);
    $contract = Contract::factory()->forClient($client)->create(['season' => '2026-2027']);
    $contract->services()->create(['label' => 'Déneigement entrée', 'amount' => 42500, 'position' => 1]);

    return $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 15000,
        'paid' => true,
        'method' => PaymentMethod::PAYMENT_VIREMENT,
        'paid_at' => now(),
        ...$paymentAttributes,
    ]);
}

function fakeReceiptPdfDisk(): FilesystemAdapter
{
    return Storage::fake((string) config('filesystems.default'));
}

it('renders the printable payment receipt', function (): void {
    $admin = User::factory()->admin()->create();
    $payment = receiptFixture($admin);

    $this->actingAs($admin)
        // The document follows the visitor's locale; pin English so the
        // assertions are deterministic (the middleware defaults to fr)...
        ->withUnencryptedCookie('locale', 'en')
        ->get(route('payments.receipt.print', $payment))
        ->assertOk()
        ->assertSee('Payment receipt')
        ->assertSee($admin->organization->name)
        ->assertSee('Jean Tremblay')
        ->assertSee('Payment details')
        ->assertSee('Deposit')
        ->assertSee('Bank transfer')
        ->assertSee('2026-2027')
        ->assertSee('$150.00')
        ->assertSee($payment->contract->client->clientNumber);
});

it('requires authentication to view the receipt', function (): void {
    $payment = receiptFixture(User::factory()->create());

    $this->get(route('payments.receipt.print', $payment))->assertRedirect(route('login'));
});

it('prevents non-admins from accessing the receipt routes', function (): void {
    $user = User::factory()->create();
    $payment = receiptFixture($user);

    $this->actingAs($user)->get(route('payments.receipt.print', $payment))->assertForbidden();
    $this->actingAs($user)->post(route('payments.receipt.generate', $payment))->assertForbidden();
    $this->actingAs($user)->get(route('payments.receipt.download', $payment))->assertForbidden();
});

it('does not find another organization payment on the receipt routes', function (): void {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->create();
    $payment = receiptFixture($other);

    $this->actingAs($admin)->get(route('payments.receipt.print', $payment))->assertNotFound();
    $this->actingAs($admin)->get(route('payments.receipt.download', $payment))->assertNotFound();
    $this->actingAs($admin)->post(route('payments.receipt.generate', $payment))->assertNotFound();
});

it('refuses a receipt for an unpaid payment', function (): void {
    $admin = User::factory()->admin()->create();
    $payment = receiptFixture($admin, ['paid' => false, 'method' => null, 'paid_at' => null]);

    $this->actingAs($admin)->get(route('payments.receipt.print', $payment))->assertNotFound();
    $this->actingAs($admin)->get(route('payments.receipt.download', $payment))->assertNotFound();
    $this->actingAs($admin)->post(route('payments.receipt.generate', $payment))->assertNotFound();
});

it('queues receipt PDF generation when none is stored yet', function (): void {
    Queue::fake();
    fakeReceiptPdfDisk();
    $admin = User::factory()->admin()->create();
    $payment = receiptFixture($admin);

    $this->actingAs($admin)
        ->post(route('payments.receipt.generate', $payment))
        ->assertRedirect()
        ->assertSessionHas('status');

    Queue::assertPushed(
        GeneratePaymentReceiptDocumentPdf::class,
        fn (GeneratePaymentReceiptDocumentPdf $job): bool => $job->paymentId === $payment->id,
    );
});

it('sends the browser to the receipt download when a PDF is already stored', function (): void {
    Queue::fake();
    fakeReceiptPdfDisk();
    $admin = User::factory()->admin()->create();
    $payment = receiptFixture($admin);
    Storage::disk((string) config('filesystems.default'))->put(GeneratePaymentReceiptPdf::pdfPath($payment->id), 'PDF');

    $this->actingAs($admin)
        ->withHeaders(['X-Inertia' => 'true'])
        ->post(route('payments.receipt.generate', $payment))
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', route('payments.receipt.download', $payment));

    Queue::assertNothingPushed();
});

it('streams the stored receipt PDF', function (): void {
    fakeReceiptPdfDisk();
    $admin = User::factory()->admin()->create();
    $payment = receiptFixture($admin);
    Storage::disk((string) config('filesystems.default'))->put(GeneratePaymentReceiptPdf::pdfPath($payment->id), '%PDF-1.4 fake');

    $this->actingAs($admin)
        ->get(route('payments.receipt.download', $payment))
        ->assertOk()
        ->assertDownload(sprintf('Recu-%s-%s-%s.pdf', $payment->contract->season, $payment->contract->client->clientNumber, 'deposit'));
});

it('returns 404 when the receipt PDF has not been generated', function (): void {
    fakeReceiptPdfDisk();
    $admin = User::factory()->admin()->create();
    $payment = receiptFixture($admin);

    $this->actingAs($admin)
        ->get(route('payments.receipt.download', $payment))
        ->assertNotFound();
});
