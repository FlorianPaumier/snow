<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class UpdateQuote
{
    /** @param array{clientId: string, season: string, services: list<array{label: string, amount: int}>, payments: array<string, array{amount: int, dueDate: string|null}>} $data */
    public function handle(User $admin, Quote $quote, array $data, bool $renewalReviewed = false): void
    {
        Gate::forUser($admin)->authorize('manage-contracts');
        DB::transaction(function () use ($admin, $quote, $data, $renewalReviewed): void {
            $locked = Quote::query()->where('organization_id', $admin->organization_id)->lockForUpdate()->findOrFail($quote->id);
            throw_if($locked->status !== 'draft', ValidationException::withMessages(['quote' => __('quotes.frozen')]));
            throw_if($locked->source_contract_id !== null && ($data['clientId'] !== $locked->client_id || $data['season'] !== $locked->season), ValidationException::withMessages(['quote' => __('renewals.identity_fixed')]));
            throw_if($locked->source_contract_id !== null && ! $renewalReviewed, ValidationException::withMessages(['renewalReviewed' => __('renewals.review_required')]));
            $client = Client::query()->where('organization_id', $admin->organization_id)->whereNotAnonymized()->findOrFail($data['clientId']);
            if ($locked->source_contract_id !== null) {
                $locked->forceFill(['renewal_reviewed_at' => now()]);
            }

            $locked->update(['client_id' => $client->id, 'season' => $data['season'], 'services' => $data['services'], 'payments' => $data['payments']]);
        });
    }
}
