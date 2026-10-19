<?php

declare(strict_types=1);

namespace App\Actions;

use App\Mail\ContractDocumentMail;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Traits\Localizable;
use Throwable;

final readonly class DeliverContractEmail
{
    use Localizable;

    public function __construct(private GenerateContractPdf $generate, private FlushOrganizationCache $flushCache) {}

    public function handle(string $contractId, string $organizationId, string $senderId, string $clientId, string $recipientKey, string $requestId, string $locale): void
    {
        $query = Contract::query()->withoutGlobalScopes()->where('organization_id', $organizationId)
            ->whereKey($contractId)->where('document_mail_request_id', $requestId);
        // A previous worker may have reached SMTP before losing its response.
        if ((clone $query)->where('document_mail_status', 'sending')->update(['document_mail_status' => 'uncertain']) > 0) {
            $this->flushCache->handle($organizationId);

            return;
        }

        if ((clone $query)->where('document_mail_status', 'queued')->update(['document_mail_status' => 'sending']) === 0) {
            return;
        }

        $transportStarted = false;
        try {
            DB::transaction(function () use ($query, $organizationId, $senderId, $clientId, $recipientKey, $locale, &$transportStarted): void {
                $contract = (clone $query)->where('document_mail_status', 'sending')->lockForUpdate()->first();
                if ($contract === null) {
                    return;
                }

                $client = Client::query()->withoutGlobalScopes()->where('organization_id', $organizationId)->lockForUpdate()->find($contract->client_id);
                if ($client === null || $client->id !== $clientId || $client->isAnonymized()
                    || ! filter_var($client->email, FILTER_VALIDATE_EMAIL)
                    || hash('sha256', mb_strtolower(mb_trim((string) $client->email))) !== $recipientKey) {
                    $contract->forceFill(['document_mail_status' => 'skipped'])->save();

                    return;
                }

                $contract->setRelation('client', $client);
                $path = $this->withLocale($locale, fn (): string => $this->generate->handle($contract));
                $transportStarted = true;
                Mail::to($client->email)->sendNow(new ContractDocumentMail($contract, $path)->locale($locale));
                $sender = User::query()->withoutGlobalScopes()->where('organization_id', $organizationId)->find($senderId);
                $contract->forceFill(['document_mail_status' => 'sent', 'document_sent_at' => now(), 'document_sent_by' => $sender?->id])->save();
            });
        } catch (Throwable $throwable) {
            $query->update(['document_mail_status' => $transportStarted ? 'uncertain' : 'queued']);
            throw_unless($transportStarted, $throwable);

            report($throwable);
        }

        $this->flushCache->handle($organizationId);
    }
}
