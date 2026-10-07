<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Tour> */
final class TourFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'worker_id' => fn (array $attributes): string => User::factory()->worker()->create(['organization_id' => $attributes['organization_id']])->id,
            'name' => 'Tournée '.fake()->city(),
            'scheduled_date' => today(),
            'departure_address' => '100 rue Saint-Paul, Montréal, Québec, Canada',
            'arrival_address' => null,
            'stops' => [],
        ];
    }
}
