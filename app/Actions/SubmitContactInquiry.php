<?php

declare(strict_types=1);

namespace App\Actions;

use App\Mail\ContactInquiryMail;
use App\Models\ContactInquiry;
use Illuminate\Support\Facades\Mail;

final readonly class SubmitContactInquiry
{
    /**
     * Persist the lead first, then email it: the inquiry row must survive
     * even when mail dispatch fails, so the insert is committed before
     * the queued send. A single insert needs no DB::transaction; the only
     * ordering rule is persist-before-send so a dead SMTP connection
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

        Mail::to(config('mail.from.address'))
            ->send(new ContactInquiryMail($attributes));

        return $inquiry;
    }
}
