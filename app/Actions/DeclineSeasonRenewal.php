<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class DeclineSeasonRenewal
{
    public function handle(User $admin, Quote $quote): void
    {
        Gate::forUser($admin)->authorize('manage-contracts');
        DB::transaction(function () use ($admin, $quote): void {
            $locked = Quote::query()->where('organization_id', $admin->organization_id)->lockForUpdate()->findOrFail($quote->id);
            throw_unless($locked->source_contract_id !== null && in_array($locked->status, ['draft', 'sent', 'declined'], true), ValidationException::withMessages(['quote' => __('quotes.frozen')]));
            $locked->forceFill(['status' => 'declined'])->save();
        });
    }
}
