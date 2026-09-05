<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\User;
use App\ValueObjects\Address;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Irreversibly erases a client's personal data (Quebec Law 25 s. 22) while
 * keeping the financial skeleton — client number, contracts, services and
 * payments — so accountability and reporting survive the erasure.
 *
 * The stored contract and payment-receipt PDFs are removed too: they embed
 * the client's name, address and contact details, and anonymization is only
 * complete once no recoverable copy of the identity remains.
 */
final readonly class AnonymizeClient
{
    public function __construct(private FlushOrganizationCache $flushCache) {}

    public function handle(Client $client, ?User $actor = null): void
    {
        throw_if(
            $client->isAnonymized(),
            ValidationException::withMessages([
                'client' => __('clients.already_anonymized'),
            ]),
        );

        $organizationId = $client->organization_id;
        $contractCount = $client->contracts()->count();

        DB::transaction(function () use ($client, $actor): void {
            // Identity fields are wiped, not reworded: nullable columns go
            // null, required string columns go empty so nothing personal
            // remains anywhere in the row.
            $client->forceFill([
                'companyName' => null,
                'firstName' => '',
                'lastName' => '',
                'address' => new Address(street: '', city: '', postalCode: '', country: ''),
                'phoneDay' => null,
                'cellPhone' => null,
                'email' => null,
                'remark' => null,
                'anonymized_at' => now(),
                'anonymized_by' => $actor?->id,
            ])->save();
        });

        $this->deleteStoredPdfs($client);

        Log::info('Client anonymized.', [
            'client_id' => $client->id,
            'client_number' => $client->clientNumber,
            'organization_id' => $organizationId,
            'contracts' => $contractCount,
            'actor_id' => $actor?->id,
        ]);

        $this->flushCache->handle($organizationId);
    }

    /**
     * Contract and receipt PDFs live on the storage disk, outside the
     * database transaction, and must be removed for the erasure to be
     * irreversible: both embed the client's identity.
     */
    private function deleteStoredPdfs(Client $client): void
    {
        $disk = Storage::disk(config()->string('filesystems.default'));

        /** @var Collection<int, Contract> $contracts eager-loaded with their payments */
        $contracts = $client->contracts()->with('payments')->get();

        foreach ($contracts as $contract) {
            $this->delete($disk, GenerateContractPdf::pdfPath($contract->id));

            foreach ($contract->payments as $payment) {
                /** @var Payment $payment */
                $this->delete($disk, GeneratePaymentReceiptPdf::pdfPath($payment->id));
            }
        }
    }

    /**
     * The erasure must not silently miss a file: a failed delete leaves a
     * recoverable copy of the identity, so the operator is warned to purge
     * the orphan manually.
     */
    private function delete(Filesystem $disk, string $path): void
    {
        if (! $disk->delete($path)) {
            Log::warning('Anonymization could not delete a stored document PDF.', [
                'path' => $path,
            ]);
        }
    }
}
