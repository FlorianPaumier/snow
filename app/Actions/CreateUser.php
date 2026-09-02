<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\PlanLimitExceeded;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreateUser
{
    public function __construct(
        private GetPlanLimits $getPlanLimits,
        private SendUserInvitation $sendUserInvitation,
    ) {}

    /**
     * Create a staff member without admin-chosen credentials: the password
     * is a throwaway until the invited person sets their own through the
     * invitation email.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): User
    {
        return DB::transaction(function () use ($attributes): User {
            $organizationId = Auth::user()?->organization_id;

            $this->assertWithinUserLimit($organizationId);

            $user = User::query()->create([
                ...$attributes,
                // New staff members join the creating admin's organization...
                'organization_id' => $attributes['organization_id'] ?? $organizationId,
                'password' => Str::password(32),
                'email_verified_at' => null,
                'invitation_accepted_at' => null,
            ]);

            $this->sendUserInvitation->handle($user, Auth::user()?->name);

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
