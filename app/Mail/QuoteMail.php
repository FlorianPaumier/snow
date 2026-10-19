<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class QuoteMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Quote $quote) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('quotes.mail_subject', ['organization' => $this->quote->organization_name]));
    }

    public function content(): Content
    {
        abort_unless(in_array($this->quote->status, ['sent', 'accepted'], true), 410);

        return new Content(markdown: 'mail.quote');
    }
}
