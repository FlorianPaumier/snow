<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use SensitiveParameter;

final readonly class CreateOrganization
{
    public function __construct(private ClaimOrganizationInvite $claimInvite) {}

    /**
     * Create an organization with its owner (admin) user and a 14-day
     * trial, atomically. When an invite code is given, it is claimed in
     * the same transaction so the code dies with the registration.
     *
     * @param  array{name: string, userName: string, email: string, termsAcceptedAt?: DateTimeInterface, termsVersion?: string, privacyVersion?: string, legalLocale?: string}  $attributes
     */
    public function handle(array $attributes, #[SensitiveParameter] string $password, #[SensitiveParameter] ?string $inviteCode = null): Organization
    {
        return DB::transaction(function () use ($attributes, $password, $inviteCode): Organization {
            // Claiming first: on conflict the whole registration rolls
            // back and no half-created organization survives...
            if ($inviteCode !== null) {
                $this->claimInvite->handle($attributes['email'], $inviteCode);
            }

            $organization = Organization::query()->create([
                'name' => $attributes['name'],
                'trial_ends_at' => now()->addDays(14),
            ]);

            $user = User::query()->create([
                'organization_id' => $organization->id,
                'name' => $attributes['userName'],
                'email' => $attributes['email'],
                'password' => $password,
                'role' => UserRole::ROLE_ADMIN,
                'terms_accepted_at' => $attributes['termsAcceptedAt'] ?? null,
                'terms_version' => $attributes['termsVersion'] ?? null,
                'privacy_version' => $attributes['privacyVersion'] ?? null,
                'legal_locale' => $attributes['legalLocale'] ?? null,
            ]);

            // The sign-up user owns the organization; ownership only moves
            // through an explicit transfer afterwards...
            $organization->update(['owner_id' => $user->id]);

            event(new Registered($user));

            // Attach the freshly created owner so callers never have to
            // re-query through the auth-bound global scope...
            return $organization->setRelation('users', new Collection([$user]));
        });
    }
}
