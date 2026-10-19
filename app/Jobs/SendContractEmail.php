<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\DeliverContractEmail;
use App\Actions\FlushOrganizationCache;
use App\Models\Contract;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Attributes\UniqueFor;
use Throwable;

#[Backoff([10, 30])]
#[Timeout(60)]
#[Tries(3)]
#[UniqueFor(300)]
final class SendContractEmail implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $contractId, public readonly string $organizationId,
        public readonly string $senderId, public readonly string $clientId, public readonly string $recipientKey,
        public readonly string $requestId, public readonly string $locale) {}

    public function uniqueId(): string
    {
        return $this->organizationId.':'.$this->requestId;
    }

    public function handle(DeliverContractEmail $deliver): void
    {
        $deliver->handle($this->contractId, $this->organizationId, $this->senderId, $this->clientId, $this->recipientKey, $this->requestId, $this->locale);
    }

    public function failed(?Throwable $exception): void
    {
        $query = Contract::query()->withoutGlobalScopes()->where('organization_id', $this->organizationId)
            ->whereKey($this->contractId)->where('document_mail_request_id', $this->requestId);
        (clone $query)->where('document_mail_status', 'queued')->update(['document_mail_status' => 'failed']);
        $query->where('document_mail_status', 'sending')->update(['document_mail_status' => 'uncertain']);
        resolve(FlushOrganizationCache::class)->handle($this->organizationId);
        if (! $exception instanceof Throwable) {
            return;
        }

        report($exception);
    }
}
