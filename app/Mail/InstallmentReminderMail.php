<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class InstallmentReminderMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * Client-facing payment reminder. The payload is pre-rendered by the
     * sending action (amounts, dates and copy are French, matching the
     * client base) so this mailable stays a thin envelope.
     *
     * @param  array{clientName: string, slotLabel: string, season: string, amount: string, dueDate: string, duePhrase: string, organizationName: string, replyTo: list<string>}  $reminder
     */
    public function __construct(
        public readonly array $reminder,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: $this->reminder['replyTo'],
            subject: sprintf(
                'Rappel de paiement — %s (%s)',
                $this->reminder['slotLabel'],
                $this->reminder['amount'],
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.installment-reminder',
        );
    }
}
