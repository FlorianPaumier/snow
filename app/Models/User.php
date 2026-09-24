<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property-read string $id
 * @property-read string $organization_id
 * @property-read Organization $organization
 * @property-read string $name
 * @property-read string $email
 * @property-read CarbonInterface|null $email_verified_at
 * @property-read CarbonInterface|null $terms_accepted_at
 * @property-read string|null $terms_version
 * @property-read string|null $privacy_version
 * @property-read string|null $legal_locale
 * @property-read CarbonInterface|null $invitation_accepted_at
 * @property-read string $password
 * @property-read UserRole $role
 * @property-read string|null $remember_token
 * @property-read string|null $login_challenge_code
 * @property-read CarbonInterface|null $login_challenge_code_expires_at
 * @property-read string|null $two_factor_secret
 * @property-read string|null $two_factor_recovery_codes
 * @property-read CarbonInterface|null $two_factor_confirmed_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
#[Hidden([
    'password',
    'remember_token',
    'login_challenge_code',
    'login_challenge_code_expires_at',
    'two_factor_secret',
    'two_factor_recovery_codes',
])]
#[Fillable([
    'organization_id',
    'name',
    'email',
    'email_verified_at',
    'terms_accepted_at',
    'terms_version',
    'privacy_version',
    'legal_locale',
    'invitation_accepted_at',
    'password',
    'role',
    'remember_token',
    'login_challenge_code',
    'login_challenge_code_expires_at',
    'two_factor_secret',
    'two_factor_recovery_codes',
    'two_factor_confirmed_at',
])]
final class User extends Authenticatable implements MustVerifyEmail
{
    use BelongsToOrganization;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasUuids;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'organization_id' => 'string',
            'name' => 'string',
            'email' => 'string',
            'email_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'terms_version' => 'string',
            'privacy_version' => 'string',
            'legal_locale' => 'string',
            'invitation_accepted_at' => 'datetime',
            'role' => UserRole::class,
            'password' => 'hashed',
            'remember_token' => 'string',
            'login_challenge_code' => 'string',
            'login_challenge_code_expires_at' => 'datetime',
            'two_factor_secret' => 'string',
            'two_factor_recovery_codes' => 'string',
            'two_factor_confirmed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * A staff member still waiting to set their own password through the
     * invitation email.
     */
    public function hasPendingInvitation(): bool
    {
        return $this->invitation_accepted_at === null;
    }

    /**
     * Whether this user owns their organization: the owner can neither be
     * deleted nor demoted, only transferred.
     */
    public function isOwner(): bool
    {
        return $this->organization->owner_id === $this->id;
    }
}
