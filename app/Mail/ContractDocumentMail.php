<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class ContractDocumentMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Contract $contract, public readonly string $pdfPath) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('contract_mail.subject', ['organization' => $this->contract->organization->name, 'season' => $this->contract->season]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.contract-document');
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return [Attachment::fromStorageDisk(config()->string('filesystems.default'), $this->pdfPath)
            ->as(sprintf('Contrat-%s-%s.pdf', $this->contract->season, $this->contract->client->clientNumber))
            ->withMime('application/pdf')];
    }
}
