<?php

declare(strict_types=1);

use App\Enums\ContactInquiryStatus;
use App\Jobs\SendContactInquiry;
use App\Mail\ContactInquiryMail;
use App\Models\ContactInquiry;
use Illuminate\Support\Facades\Mail;

it('sends the inquiry email and marks the inquiry sent', function (): void {
    Mail::fake();

    $inquiry = ContactInquiry::factory()->withCompany()->create();

    new SendContactInquiry($inquiry->id)->handle();

    Mail::assertSent(ContactInquiryMail::class, 1);
    Mail::assertSent(ContactInquiryMail::class, function (ContactInquiryMail $mail) use ($inquiry): bool {
        if ($mail->envelope()->replyTo[0]->address !== $inquiry->email) {
            return false;
        }

        return str_contains($mail->render(), $inquiry->message);
    });

    expect($inquiry->fresh()->status)->toBe(ContactInquiryStatus::STATUS_SENT)
        ->and($inquiry->fresh()->sent_at)->not->toBeNull();
});

it('does not resend an inquiry already marked sent', function (): void {
    Mail::fake();

    $inquiry = ContactInquiry::factory()->sent()->create();

    new SendContactInquiry($inquiry->id)->handle();

    Mail::assertNothingSent();
    expect($inquiry->fresh()->sent_at->format('Y-m-d H:i:s'))
        ->toBe($inquiry->sent_at->format('Y-m-d H:i:s'));
});

it('marks the inquiry failed when retries are exhausted', function (): void {
    $inquiry = ContactInquiry::factory()->create();

    new SendContactInquiry($inquiry->id)
        ->failed(new RuntimeException('SMTP connection failed'));

    expect($inquiry->fresh()->status)->toBe(ContactInquiryStatus::STATUS_FAILED)
        ->and($inquiry->fresh()->failed_at)->not->toBeNull();
});

it('leaves a sent inquiry untouched when the job fails after delivery', function (): void {
    $inquiry = ContactInquiry::factory()->sent()->create();

    new SendContactInquiry($inquiry->id)
        ->failed(new RuntimeException('worker crashed after send'));

    expect($inquiry->fresh()->status)->toBe(ContactInquiryStatus::STATUS_SENT)
        ->and($inquiry->fresh()->failed_at)->toBeNull();
});
