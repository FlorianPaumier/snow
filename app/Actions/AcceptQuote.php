<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class AcceptQuote
{
    public function __construct(private CreateContract $create) {}

    public function handle(Quote $quote): Contract
    {
        return DB::transaction(function () use ($quote): Contract {
            $locked = Quote::query()->withoutGlobalScopes()->where('organization_id', $quote->organization_id)->lockForUpdate()->findOrFail($quote->id);
            abort_unless(in_array($locked->status, ['sent', 'accepted'], true), 410);
            abort_if($locked->expires_at === null || $locked->expires_at->isPast(), 410);
            $client = Client::query()->withoutGlobalScopes()->where('organization_id', $locked->organization_id)->whereNotAnonymized()->lockForUpdate()->findOrFail($locked->client_id);
            if ($locked->accepted_at !== null) {
                return Contract::query()->withoutGlobalScopes()->where('organization_id', $locked->organization_id)->findOrFail($locked->contract_id);
            }

            throw_if(Contract::query()->withoutGlobalScopes()->where('organization_id', $locked->organization_id)->where('client_id', $client->id)->where('season', $locked->season)->exists(), ValidationException::withMessages(['quote' => __('quotes.conflict')]));
            $contract = $this->create->handle(['client' => $client, 'season' => $locked->season, 'services' => $locked->services, 'payments' => $locked->payments]);
            $locked->forceFill(['status' => 'accepted', 'accepted_at' => now(), 'contract_id' => $contract->id])->save();

            return $contract;
        });
    }
}
