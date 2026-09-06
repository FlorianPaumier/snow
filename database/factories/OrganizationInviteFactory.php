<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OrganizationInvite;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<OrganizationInvite>
 */
final class OrganizationInviteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'code' => Hash::make(mb_strtoupper(Str::random(10))),
            'expires_at' => now()->addDays(7),
        ];
    }

    public function expired(): self
    {
        return $this->state(fn (): array => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function claimed(): self
    {
        return $this->state(fn (): array => [
            'claimed_at' => now(),
        ]);
    }
}
