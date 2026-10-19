<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contract;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final readonly class DeleteContract
{
    public function __construct(private FlushOrganizationCache $flushCache) {}

    public function handle(Contract $contract): void
    {
        $organizationId = $contract->organization_id;
        DB::transaction(function () use ($contract): void {
            $contract = Contract::query()->lockForUpdate()->findOrFail($contract->id);
            $payments = $contract->payments()->lockForUpdate()->get();
            throw_if($payments->contains(fn (Payment $payment): bool => $payment->paid || $payment->stripe_checkout_key !== null),
                ValidationException::withMessages(['contract' => __('contracts.delete_with_paid')]));
            $contract->delete();
        });

        // The stored PDF embeds the client's personal data; it must not
        // outlive the record it belongs to...
        Storage::disk(config()->string('filesystems.default'))
            ->delete(GenerateContractPdf::pdfPath($contract->id));

        $this->flushCache->handle($organizationId);
    }
}
