<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\GenerateContractPdf;
use App\Models\Contract;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class GenerateContractDocumentPdf implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 30];

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
}
