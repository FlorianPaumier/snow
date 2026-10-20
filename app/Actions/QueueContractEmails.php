<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\SendContractEmail;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class QueueContractEmails
{
    public function __construct(private FlushOrganizationCache $flushCache) {}

    /** @param list<string> $contractIds */
    public function handle(User $admin, array $contractIds): int
    {
        Gate::forUser($admin)->authorize('manage-contracts');
        $count = DB::transaction(function () use ($admin, $contractIds): int {
            $contracts = Contract::query()->where('organization_id', $admin->organization_id)
                ->whereIn('id', $contractIds)->with('client')->orderBy('id')->lockForUpdate()->get();
            abort_if($contractIds === [] || $contracts->count() !== count($contractIds), 404);
            foreach ($contracts as $contract) {
                throw_if($contract->client->isAnonymized() || ! filter_var($contract->client->email, FILTER_VALIDATE_EMAIL),
                    ValidationException::withMessages(['contracts' => __('contract_mail.no_email', ['client' => $contract->client->displayName()])]));
            }

            $queued = 0;
            foreach ($contracts as $contract) {
                if (in_array($contract->document_mail_status, ['queued', 'sending'], true)) {
                    continue;
                }

                $requestId = (string) Str::uuid();
                $contract->forceFill(['document_mail_status' => 'queued', 'document_mail_request_id' => $requestId])->save();
                $job = new SendContractEmail($contract->id, $admin->organization_id, $admin->id,
                    $contract->client_id, hash('sha256', mb_strtolower(mb_trim((string) $contract->client->email))),
                    $requestId, app()->getLocale());
                DB::afterCommit(function () use ($job): void {
                    try {
                        dispatch($job);
                    } catch (Throwable $throwable) {
                        $job->failed($throwable);
                    }
                });
                $queued++;
            }

            return $queued;
        });
        $this->flushCache->handle($admin->organization_id);

        return $count;
    }
}
