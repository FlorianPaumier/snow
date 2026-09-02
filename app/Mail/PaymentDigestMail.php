<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class PaymentDigestMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * Internal weekly digest for an organization: installments due soon or
     * already late. Rows are pre-rendered by the sending action.
     *
     * @param  array{organizationName: string, overdue: list<array{clientName: string, slotLabel: string, season: string, amount: string, dueDate: string}>, upcoming: list<array{clientName: string, slotLabel: string, season: string, amount: string, dueDate: string, duePhrase: string}>}  $digest
     */
    public function __construct(
        public readonly array $digest,
    ) {}

    public function envelope(): Envelope
    {
        $overdueCount = count($this->digest['overdue']);
        $upcomingCount = count($this->digest['upcoming']);

        return new Envelope(
            subject: sprintf(
                'Paiements — %d en retard, %d à venir',
                $overdueCount,
                $upcomingCount,
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.payment-digest',
        );
    }
}
