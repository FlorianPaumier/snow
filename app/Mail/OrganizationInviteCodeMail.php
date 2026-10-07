<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class OrganizationInviteCodeMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly string $expiresAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your invitation code',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.organization-invite-code',
            with: [
                'code' => $this->code,
                'expiresAt' => $this->expiresAt,
                'url' => route('register', absolute: true),
            ],
        );
    }
}
