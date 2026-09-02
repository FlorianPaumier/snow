<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ContactInquiryStatus;
use App\Mail\ContactInquiryMail;
use App\Models\ContactInquiry;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class SendContactInquiry implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 30];

    public function __construct(public readonly string $inquiryId) {}

    /**
     * One email per inquiry: a duplicate dispatch racing a first send
     * would be caught by the status guard only after both checked it.
     */
    public function uniqueId(): string
    {
        return $this->inquiryId;
    }

    /**
     * Queue jobs run unauthenticated and the inquiry is not
     * organization-scoped, so it is resolved globally by id.
     */
    public function handle(): void
    {
        $inquiry = ContactInquiry::query()->findOrFail($this->inquiryId);

        // Re-read status on every attempt: a retried or manually re-queued
        // job must never email the same lead twice.
        if ($inquiry->status->isSent()) {
            return;
        }

        // sendNow, not send: the mailable itself implements ShouldQueue, but
        // this job is already the queued retry unit — re-queueing here would
        // mark the inquiry sent before SMTP has accepted anything.
        Mail::to(config('mail.from.address'))
            ->sendNow(new ContactInquiryMail($inquiry));

        $inquiry->forceFill([
            'status' => ContactInquiryStatus::STATUS_SENT,
            'sent_at' => now(),
        ])->save();
    }

    /**
     * Retries exhausted: park the inquiry as failed so the lead stays
     * visible (and re-sendable) instead of silently lost in failed_jobs.
     */
    public function failed(?Throwable $exception): void
    {
        $inquiry = ContactInquiry::query()->find($this->inquiryId);

        if ($inquiry === null || $inquiry->status->isSent()) {
            return;
        }

        $inquiry->forceFill([
            'status' => ContactInquiryStatus::STATUS_FAILED,
            'failed_at' => now(),
        ])->save();

        Log::error('Contact inquiry delivery failed', [
            'inquiry_id' => $this->inquiryId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
