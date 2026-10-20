<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class UserInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $email,
        public readonly string $organizationName,
        public readonly string $token,
        public readonly ?string $inviterName = null,
    ) {
        $this->locale(app()->getLocale());
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('invitations.user_subject', ['organization' => $this->organizationName, 'app' => config()->string('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.user-invitation',
            with: [
                'email' => $this->email,
                'organizationName' => $this->organizationName,
                'inviterName' => $this->inviterName,
                'url' => route('user-invitation.create', ['token' => $this->token], absolute: false)
                    .'?email='.urlencode($this->email),
            ],
        );
    }
}
