<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\InviteClaimConflict;
use App\Models\OrganizationInvite;
use Illuminate\Support\Facades\Hash;

final readonly class ClaimOrganizationInvite
{
    /**
     * Burn the invite matching the code, inside the caller's transaction.
     *
     * The validator already proved the invite is redeemable; this flips
     * claimed_at under a WHERE guard so a concurrent double-claim affects
     * zero rows and the registration rolls back instead of forking two
     * organizations off one code.
     */
    public function handle(string $email, string $code): void
    {
        $claimed = OrganizationInvite::query()
            ->where('email', mb_strtolower($email))
            ->whereNull('claimed_at')
            ->where('expires_at', '>', now())->latest()
            ->get()
            ->first(fn (OrganizationInvite $invite) => Hash::check($code, $invite->code));

        // Raced or tampered between validation and claim: aborting
        // throws out of the surrounding transaction...
        throw_if($claimed === null, InviteClaimConflict::class);

        $claimed->forceFill(['claimed_at' => now()])->save();
    }
}
