<?php

declare(strict_types=1);

use App\Actions\GenerateContractPdf;
use App\Enums\PaymentSlot;
use App\Jobs\GenerateContractDocumentPdf;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * A contract with one service and an unpaid deposit.
 */
function pdfContract(User $user): Contract
{
    $client = Client::factory()->forOrganization($user->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();
    $contract->services()->create(['label' => 'Déneigement entrée', 'amount' => 42500, 'position' => 1]);
    $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 15000,
        'paid' => false,
        'method' => null,
        'paid_at' => null,
    ]);

    return $contract;
}

function fakePdfDisk(): FilesystemAdapter
{
    return Storage::fake((string) config('filesystems.default'));
}

function fakeGotenberg(): void
{
    Http::fake([
        '*/forms/chromium/convert/html' => Http::response('%PDF-1.4 fake bytes', 200, [
            'Content-Type' => 'application/pdf',
        ]),
    ]);
}

it('renders the document and stores the Gotenberg PDF', function (): void {
    fakePdfDisk();
    fakeGotenberg();
    $user = User::factory()->create();
    $contract = pdfContract($user);

    $path = resolve(GenerateContractPdf::class)->handle($contract);

    expect($path)->toBe(GenerateContractPdf::pdfPath($contract->id));

    $disk = Storage::disk((string) config('filesystems.default'));
    $disk->assertExists($path);

    expect($disk->get($path))->toBe('%PDF-1.4 fake bytes');

    Http::assertSent(fn (Request $request): bool => str_contains((string) $request->url(), '/forms/chromium/convert/html'));
});

it('fails when Gotenberg rejects the render', function (): void {
    Http::fake([
        '*/forms/chromium/convert/html' => Http::response('boom', 500),
    ]);
    $user = User::factory()->create();
    $contract = pdfContract($user);

    resolve(GenerateContractPdf::class)->handle($contract);
})->throws(RequestException::class);

it('resolves the contract from its id and generates the pdf', function (): void {
    $disk = fakePdfDisk();
    fakeGotenberg();
    $user = User::factory()->create();
    $contract = pdfContract($user);

    new GenerateContractDocumentPdf($contract->id)->handle(resolve(GenerateContractPdf::class));

    $disk->assertExists(GenerateContractPdf::pdfPath($contract->id));
});
