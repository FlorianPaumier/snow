<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\ContactInquiry;
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

    public function __construct(
        public readonly ContactInquiry $inquiry,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [$this->inquiry->email],
            subject: __('invitations.contact_subject', ['name' => $this->inquiry->name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.contact-inquiry',
        );
    }
}
