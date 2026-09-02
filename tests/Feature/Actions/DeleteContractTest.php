<?php

declare(strict_types=1);

use App\Actions\GenerateContractPdf;
use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

function deleteContractPdfDisk(): FilesystemAdapter
{
    return Storage::fake((string) config('filesystems.default'));
}

function deletableContract(User $user): Contract
{
    $client = Client::factory()->forOrganization($user->organization)->create();

    return Contract::factory()->forClient($client)->create();
}

it('removes the stored pdf when a contract is deleted', function (): void {
    $disk = deleteContractPdfDisk();
    $user = User::factory()->create();
    $contract = deletableContract($user);

    $disk->put(GenerateContractPdf::pdfPath($contract->id), '%PDF-1.4 fake bytes');

    app(App\Actions\DeleteContract::class)->handle($contract);

    expect($contract->fresh())->toBeNull();
    $disk->assertMissing(GenerateContractPdf::pdfPath($contract->id));
});

it('keeps the pdf when the contract cannot be deleted', function (): void {
    $disk = deleteContractPdfDisk();
    $user = User::factory()->create();
    $contract = deletableContract($user);
    $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 15000,
        'paid' => true,
    ]);

    $disk->put(GenerateContractPdf::pdfPath($contract->id), '%PDF-1.4 fake bytes');

    expect(fn (): null => app(App\Actions\DeleteContract::class)->handle($contract))
        ->toThrow(ValidationException::class);

    expect($contract->fresh())->not->toBeNull();
    $disk->assertExists(GenerateContractPdf::pdfPath($contract->id));
});
