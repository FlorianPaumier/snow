<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\OrganizationInviteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Single-use invite code gating public organization sign-up. Only the
 * operator sends these (via the organization:send-invite-code command);
 * a code is bound to one prospect email and dies once claimed.
 *
 * Deliberately not organization-scoped: invites exist before any
 * organization or user account does.
 *
 * @property-read int $id
 * @property-read string $email
 * @property-read string $code
 * @property-read CarbonInterface $expires_at
 * @property-read CarbonInterface|null $claimed_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
#[Fillable([
    'email',
    'code',
    'expires_at',
    'claimed_at',
])]
final class OrganizationInvite extends Model
{
    /** @use HasFactory<OrganizationInviteFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'email' => 'string',
            'code' => 'string',
            'expires_at' => 'datetime',
            'claimed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Whether the invite has already founded an organization.
     */
    public function isClaimed(): bool
    {
        return $this->claimed_at !== null;
    }

    /**
     * Whether the invite can no longer be redeemed.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Whether the invite is redeemable by the given email at all,
     * regardless of the code matching.
     */
    public function isRedeemableBy(string $email): bool
    {
        return ! $this->isClaimed()
            && ! $this->isExpired()
            && $this->email === mb_strtolower($email);
    }
}
