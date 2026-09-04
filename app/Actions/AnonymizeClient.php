<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Client;
use App\Models\User;
use App\ValueObjects\Address;
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
 * The stored contract PDFs are removed too: they embed the client's name,
 * address and contact details, and anonymization is only complete once no
 * recoverable copy of the identity remains.
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

        $this->deleteContractPdfs($client);

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
     * PDFs live on the storage disk, outside the database transaction, and
     * must be removed for the erasure to be irreversible.
     */
    private function deleteContractPdfs(Client $client): void
    {
        $disk = Storage::disk(config()->string('filesystems.default'));

        /** @var Collection<int, string> $contractIds */
        $contractIds = $client->contracts()->pluck('id');

        foreach ($contractIds as $contractId) {
            $disk->delete(GenerateContractPdf::pdfPath($contractId));
        }
    }
}
