<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\SendContactInquiry;
use App\Models\ContactInquiry;

final readonly class SubmitContactInquiry
{
    /**
     * Persist the lead first, then queue its delivery.
     *
     * Constraint honored: the inquiry row must survive even if mail dispatch
     * fails, so the insert is committed before the send job is handed to the
     * queue. A single insert needs no DB::transaction; the only ordering
     * rule is persist-before-dispatch so a failed SMTP/queue connection
     * never silently loses the lead.
     *
     * @param  array<string, ?string>  $attributes
     */
    public function handle(array $attributes): ContactInquiry
    {
        $inquiry = ContactInquiry::query()->create([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'company' => $attributes['company'] ?? null,
            'fleetSize' => $attributes['fleetSize'] ?? null,
            'message' => $attributes['message'],
        ]);

        SendContactInquiry::dispatch($inquiry->id);

        return $inquiry;
    }
}
