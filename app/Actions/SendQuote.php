<?php

declare(strict_types=1);

namespace App\Actions;

use App\Mail\QuoteMail;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

final readonly class SendQuote
{
    public function handle(User $admin, Quote $quote): void
    {
        Gate::forUser($admin)->authorize('manage-contracts');
        DB::transaction(function () use ($admin, $quote): void {
            $locked = Quote::query()->where('organization_id', $admin->organization_id)->lockForUpdate()->findOrFail($quote->id);
            if ($locked->status === 'sent') {
                return;
            }

            throw_if($locked->status !== 'draft', ValidationException::withMessages(['quote' => __('quotes.frozen')]));
            throw_if($locked->source_contract_id !== null && $locked->renewal_reviewed_at === null, ValidationException::withMessages(['quote' => __('renewals.review_required')]));
            $client = Client::query()->where('organization_id', $locked->organization_id)->whereNotAnonymized()->lockForUpdate()->findOrFail($locked->client_id);
            throw_if(Contract::query()->where('organization_id', $locked->organization_id)->where('client_id', $client->id)->where('season', $locked->season)->exists(), ValidationException::withMessages(['quote' => __('quotes.conflict')]));
            throw_unless(is_string($client->email) && filter_var($client->email, FILTER_VALIDATE_EMAIL), ValidationException::withMessages(['quote' => __('quotes.no_email')]));
            $locked->forceFill(['status' => 'sent', 'sent_at' => now(), 'expires_at' => now()->addDays(14),
                'organization_name' => $admin->organization->name,
                'customer' => ['name' => $client->displayName(), 'address' => implode(', ', [$client->address->street, $client->address->city, $client->address->postalCode, $client->address->country]), 'email' => $client->email]])->save();
            Mail::to($client->email)->send(new QuoteMail($locked)->locale(app()->getLocale())->afterCommit());
        });
    }
}
