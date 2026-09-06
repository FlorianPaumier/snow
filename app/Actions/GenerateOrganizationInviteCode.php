<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\OrganizationInvite;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class GenerateOrganizationInviteCode
{
    /**
     * How long an invite code stays redeemable after it is issued.
     */
    public const int LIFETIME_DAYS = 7;

    /**
     * Create a single-use invite code bound to the prospect's email.
     *
     * The plaintext code is returned once for delivery; only its SHA-256
     * hash is stored, so a leaked database row cannot be redeemed.
     */
    public function handle(string $email): string
    {
        $code = mb_strtoupper(Str::random(10));

        OrganizationInvite::query()->create([
            'email' => mb_strtolower($email),
            'code' => Hash::make($code),
            'expires_at' => now()->addDays(self::LIFETIME_DAYS),
        ]);

        return $code;
    }
}
