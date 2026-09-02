<?php

declare(strict_types=1);

use App\Actions\AnonymizeClient;
use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

function anonymizePdfDisk(): FilesystemAdapter
{
    return Storage::fake((string) config('filesystems.default'));
}

it('erases identity fields and keeps the financial skeleton', function (): void {
    $user = User::factory()->create();
    $client = Client::factory()->forOrganization($user->organization)->company()->create([
        'companyName' => 'Gestion Tremblay',
        'email' => 'jean@example.com',
        'phoneDay' => '514 555-0100',
        'remark' => 'Chien sur place',
    ]);
    $contract = Contract::factory()->forClient($client)->create(['season' => '2024-2025']);
    $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 15000,
        'paid' => true,
    ]);
    $number = $client->clientNumber;

    app(AnonymizeClient::class)->handle($client, $user);

    $client->refresh();
    expect($client->isAnonymized())->toBeTrue()
        ->and($client->firstName)->toBe('')
        ->and($client->lastName)->toBe('')
        ->and($client->companyName)->toBeNull()
        ->and($client->email)->toBeNull()
        ->and($client->phoneDay)->toBeNull()
        ->and($client->cellPhone)->toBeNull()
        ->and($client->remark)->toBeNull()
        ->and($client->address->street)->toBe('')
        ->and($client->anonymized_at)->not->toBeNull()
        ->and($client->anonymized_by)->toBe($user->id)
        // Accountability: number, contracts and payments survive...
        ->and($client->clientNumber)->toBe($number)
        ->and($client->contracts()->count())->toBe(1)
        ->and($contract->payments()->where('paid', true)->count())->toBe(1);
});

it('shows the placeholder name and refuses re-anonymization', function (): void {
    $user = User::factory()->create();
    $client = Client::factory()->forOrganization($user->organization)->create();

    app(AnonymizeClient::class)->handle($client, $user);
    $client->refresh();

    expect($client->displayName())->toBe(__('clients.anonymized_name'));

    app(AnonymizeClient::class)->handle($client->fresh(), $user);
})->throws(ValidationException::class);

it('deletes the stored contract pdfs of the anonymized client', function (): void {
    $disk = anonymizePdfDisk();
    $user = User::factory()->create();
    $client = Client::factory()->forOrganization($user->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();
    // Another organization's contract PDF must survive...
    $other = Contract::factory()->create();

    foreach ([$contract, $other] as $contractWithPdf) {
        $disk->put(
            App\Actions\GenerateContractPdf::pdfPath($contractWithPdf->id),
            '%PDF-1.4 fake bytes',
        );
    }

    app(AnonymizeClient::class)->handle($client, $user);

    $disk->assertMissing(App\Actions\GenerateContractPdf::pdfPath($contract->id));
    $disk->assertExists(App\Actions\GenerateContractPdf::pdfPath($other->id));
});
