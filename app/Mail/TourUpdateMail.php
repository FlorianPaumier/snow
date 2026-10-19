<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class TourUpdateMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $organizationName, public readonly string $event, public readonly string $unsubscribeUrl, public readonly ?string $replyToEmail = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config()->string('mail.from.address'), $this->organizationName),
            replyTo: $this->replyToEmail !== null ? [new Address($this->replyToEmail)] : [],
            subject: __('tour_notifications.mail_subject', ['organization' => $this->organizationName]),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.tour-update');
    }
}
