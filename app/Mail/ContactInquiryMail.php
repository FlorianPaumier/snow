<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class ContactInquiryMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, ?string>  $inquiry
     */
    public function __construct(
        public readonly array $inquiry,
    ) {}

    public function envelope(): Envelope
    {
        $replyTo = $this->inquiry['email'];

        return new Envelope(
            replyTo: is_string($replyTo) ? [$replyTo] : [],
            subject: 'Contact inquiry from '.$this->inquiry['name'],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.contact-inquiry',
        );
    }
}
