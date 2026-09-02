<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\PlanLimitExceeded;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use SensitiveParameter;

final readonly class CreateUser
{
    public function __construct(private GetPlanLimits $getPlanLimits) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes, #[SensitiveParameter] string $password): User
    {
        return DB::transaction(function () use ($attributes, $password): User {
            $organizationId = Auth::user()?->organization_id;

            $this->assertWithinUserLimit($organizationId);

            $user = User::query()->create([
                ...$attributes,
                // New staff members join the creating admin's organization...
                'organization_id' => $attributes['organization_id'] ?? $organizationId,
                'password' => $password,
            ]);

            event(new Registered($user));

            return $user;
        });
    }

    /**
     * Enforce the organization's plan limit on users, blocking the create
     * with an upgrade prompt when reached.
     */
    private function assertWithinUserLimit(?string $organizationId): void
    {
        if ($organizationId === null) {
            return;
        }

        /** @var Organization $organization */
        $organization = Organization::query()->findOrFail($organizationId);

        $limits = $this->getPlanLimits->handle($organization);

        if ($limits['maxUsers'] === null) {
            return;
        }

        $userCount = User::query()->withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->count();

        if ($userCount >= $limits['maxUsers']) {
            /** @var array{plan: string, maxUsers: int} $limits */
            throw new PlanLimitExceeded(
                __('statuses.plan_limit', ['plan' => $limits['plan'], 'count' => $limits['maxUsers'], 'resource' => __('statuses.resource_users')]),
            );
        }
    }
}
