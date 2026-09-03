<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OnboardingStep;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * The didactic "get started" checklist for the dashboard. Step completion
 * is derived from the organization's data on every request, so the
 * checklist can never drift out of sync with reality.
 */
final readonly class GetOnboardingChecklist
{
    /**
     * @param  User|null  $user  Defaults to the authenticated user
     * @return array{steps: array<int, array{key: string, href: string, completed: bool}>, incompleteCount: int}|null
     */
    public function handle(?User $user = null): ?array
    {
        $user ??= Auth::user();

        if ($user === null) {
            return null;
        }

        $steps = [];

        foreach (OnboardingStep::cases() as $step) {
            if (! $step->isVisibleFor($user)) {
                continue;
            }

            $steps[] = [
                'key' => $step->value,
                'href' => route($step->routeName()),
                'completed' => $step->isCompletedFor($user),
            ];
        }

        return [
            'steps' => $steps,
            'incompleteCount' => count(
                array_filter(
                    $steps,
                    fn (array $step): bool => ! $step['completed'],
                ),
            ),
        ];
    }
}
