<?php

declare(strict_types=1);

namespace App\Mail;

use App\Actions\SendPaymentReminder;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\SentMessage;
use Illuminate\Queue\SerializesModels;

final class InstallmentReminderMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /** @var array{clientName: string, slotLabel: string, season: string, amount: string, dueDate: string, duePhrase: string, organizationName: string, paymentUrl?: string|null, replyTo: list<string>} */
    public array $reminder;

    public ?int $paymentId = null;

    public ?string $organizationId = null;

    public function __construct(int $paymentId, string $organizationId)
    {
        $this->paymentId = $paymentId;
        $this->organizationId = $organizationId;
    }

    public function send($mailer): ?SentMessage
    {
        // Discard legacy queued snapshots, which have no identifiers to revalidate.
        if ($this->paymentId === null || $this->organizationId === null) {
            return null;
        }

        $payment = Payment::query()->withoutGlobalScopes()->where('organization_id', $this->organizationId)
            ->with(['contract' => fn (Builder $query): Builder => $query->withoutGlobalScopes(),
                'contract.client' => fn (Builder $query): Builder => $query->withoutGlobalScopes(), 'organization'])
            ->find($this->paymentId);
        if ($payment === null || $payment->paid || in_array($payment->stripe_status, ['processing', 'failed'], true)
            || $payment->contract->client->isAnonymized() || ! $payment->contract->client->email) {
            return null;
        }

        $this->reminder = resolve(SendPaymentReminder::class)->details($payment);
        $this->to = [];
        $this->to($payment->contract->client->email);

        return parent::send($mailer);
    }

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
