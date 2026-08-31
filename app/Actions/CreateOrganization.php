<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use SensitiveParameter;

final readonly class CreateOrganization
{
    /**
     * Create an organization with its owner (admin) user and a 14-day
     * trial, atomically.
     *
     * @param  array{name: string, userName: string, email: string}  $attributes
     */
    public function handle(array $attributes, #[SensitiveParameter] string $password): Organization
    {
        return DB::transaction(function () use ($attributes, $password): Organization {
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
            ]);

            event(new Registered($user));

            // Attach the freshly created owner so callers never have to
            // re-query through the auth-bound global scope...
            return $organization->setRelation('users', new Collection([$user]));
        });
    }
}
