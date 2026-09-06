<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\OrganizationInvite;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates the sign-up invite code against the hashed, unclaimed invite
 * bound to the registering email. Keeps the check and the eventual claim
 * on the same rule so UserController never re-implements matching.
 */
final class ValidOrganizationInviteCode implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    private array $data = [];

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        assert(is_string($value));

        $email = $this->data['email'] ?? null;

        if (! is_string($email)) {
            $fail(__('validation.invite_code'));

            return;
        }

        $invite = OrganizationInvite::query()
            ->where('email', mb_strtolower($email))
            ->whereNull('claimed_at')->latest()
            ->first();

        if ($invite === null
            || $invite->expires_at->isPast()
            || ! Hash::check($value, $invite->code)) {
            $fail(__('validation.invite_code'));
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }
}
