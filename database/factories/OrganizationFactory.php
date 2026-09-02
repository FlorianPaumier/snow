<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
final class OrganizationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => sprintf('%s Déneigement', fake()->company()),
            'trial_ends_at' => now()->addDays(14),
        ];
    }

    public function withoutTrial(): self
    {
        return $this->state(fn (): array => [
            'trial_ends_at' => null,
        ]);
    }

    public function ownedBy(User $user): self
    {
        return $this->state(fn (): array => [
            'owner_id' => $user->id,
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn (): array => [
            'trial_ends_at' => now()->subDay(),
        ]);
    }

    public function unlocked(): self
    {
        return $this->state(fn (): array => [
            'access_unlock_until' => now()->addHours(24),
        ]);
    }
}
