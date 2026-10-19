<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Organization;
use App\Models\User;
use App\Support\StripeConnect;
use Illuminate\Support\Facades\DB;

final readonly class OnboardStripeAccount
{
    public function __construct(private StripeConnect $stripe) {}

    public function handle(User $user): string
    {
        $accountId = DB::transaction(function () use ($user): string {
            $organization = Organization::query()->lockForUpdate()->findOrFail($user->organization_id);
            if ($organization->stripe_connect_id === null) {
                $account = $this->stripe->createAccount($organization, $user->email);
                $organization->forceFill(['stripe_connect_id' => $account->id])->save();
            }

            return (string) $organization->stripe_connect_id;
        });

        return $this->stripe->onboardingUrl($accountId);
    }
}
