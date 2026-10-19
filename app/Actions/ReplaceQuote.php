<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class ReplaceQuote
{
    public function __construct(private CreateQuote $create) {}

    public function handle(User $admin, Quote $quote): Quote
    {
        Gate::forUser($admin)->authorize('manage-contracts');

        return DB::transaction(function () use ($admin, $quote): Quote {
            $locked = Quote::query()->where('organization_id', $admin->organization_id)->lockForUpdate()->findOrFail($quote->id);
            throw_if($locked->status !== 'sent' && ! ($locked->source_contract_id !== null && $locked->status === 'declined'), ValidationException::withMessages(['quote' => __('quotes.frozen')]));
            $replacement = $this->create->handle($admin, ['clientId' => $locked->client_id, 'season' => $locked->season, 'services' => $locked->services, 'payments' => $locked->payments]);
            $replacement->forceFill(['source_contract_id' => $locked->source_contract_id])->save();
            $locked->forceFill(['status' => 'replaced'])->save();

            return $replacement;
        });
    }
}
