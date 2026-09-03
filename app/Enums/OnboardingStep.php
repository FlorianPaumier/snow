<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\User;

/**
 * The didactic onboarding checklist shown on the dashboard until the
 * organization has performed each key activation action. Completion is
 * derived from domain state on every request (no stored progress), and
 * each step is only suggested to users who can actually perform it.
 */
enum OnboardingStep: string
{
    case STEP_FIRST_CLIENT = 'first_client';

    case STEP_FIRST_CONTRACT = 'first_contract';

    case STEP_FIRST_PAID_INSTALLMENT = 'first_paid_installment';

    case STEP_INVITE_TEAMMATE = 'invite_teammate';

    /**
     * The ability (Laravel gate) required to perform this step.
     */
    public function ability(): string
    {
        return match ($this) {
            self::STEP_FIRST_CLIENT => 'manage-clients',
            self::STEP_FIRST_CONTRACT => 'manage-contracts',
            self::STEP_FIRST_PAID_INSTALLMENT => 'manage-contracts',
            self::STEP_INVITE_TEAMMATE => 'manage-users',
        };
    }

    /**
     * The named route of the page where the step is performed.
     */
    public function routeName(): string
    {
        return match ($this) {
            self::STEP_FIRST_CLIENT => 'clients.create',
            self::STEP_FIRST_CONTRACT => 'contracts.create',
            self::STEP_FIRST_PAID_INSTALLMENT => 'dashboard',
            self::STEP_INVITE_TEAMMATE => 'users.create',
        };
    }

    /**
     * Whether the user should see this step at all: a step is either
     * already completed (shown ticked for anyone) or actionable by them.
     */
    public function isVisibleFor(User $user): bool
    {
        return $this->isCompletedFor($user) || $user->can($this->ability());
    }

    /**
     * Whether the organization has performed the action behind this step.
     */
    public function isCompletedFor(User $user): bool
    {
        $organization = $user->organization;

        return match ($this) {
            self::STEP_FIRST_CLIENT => $organization->clients()->exists(),
            self::STEP_FIRST_CONTRACT => $organization->contracts()->exists(),
            self::STEP_FIRST_PAID_INSTALLMENT => $organization->payments()->where('paid', true)->exists(),
            // CreateUser (the invite flow) is the only path to a second
            // user, so a teammate existing means an invite was sent...
            self::STEP_INVITE_TEAMMATE => $organization->users()->count() > 1,
        };
    }
}
