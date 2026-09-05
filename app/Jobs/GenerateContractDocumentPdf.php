<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\GenerateContractPdf;
use App\Models\Contract;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Backoff([10, 30])]
#[Tries(3)]
final class GenerateContractDocumentPdf implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $contractId) {}

    /**
     * One PDF per contract: repeated clicks on "generate" must not stack
     * duplicate conversions of the same document.
     */
    public function uniqueId(): string
    {
        return $this->contractId;
    }

    /**
     * Queue jobs run unauthenticated, so the organization scope does not
     * apply and the contract is resolved globally by id.
     */
    public function handle(GenerateContractPdf $generate): void
    {
        $generate->handle(Contract::query()->findOrFail($this->contractId));
    }

    /**
     * Retries exhausted: the contract page links back to the generate
     * button, so the operator only needs to know the generation died —
     * nothing else is parked or lost.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('Contract document PDF generation failed', [
            'contract_id' => $this->contractId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
