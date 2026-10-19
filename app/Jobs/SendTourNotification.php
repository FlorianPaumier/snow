<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\DeliverTourNotification;
use App\Models\TourNotification;
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
final class SendTourNotification implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $notificationId, public readonly string $organizationId) {}

    public function uniqueId(): string
    {
        return $this->organizationId.':'.$this->notificationId;
    }

    public function handle(DeliverTourNotification $deliver): void
    {
        $deliver->handle($this->notificationId, $this->organizationId);
    }

    public function failed(?Throwable $exception): void
    {
        TourNotification::query()->withoutGlobalScopes()->where('organization_id', $this->organizationId)->whereKey($this->notificationId)
            ->whereIn('status', ['pending', 'sending'])->update(['status' => 'uncertain', 'reason' => 'delivery_unconfirmed']);
        if (! $exception instanceof Throwable) {
            return;
        }

        report($exception);
    }
}
