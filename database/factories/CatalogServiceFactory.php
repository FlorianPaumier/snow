<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CatalogService;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CatalogService> */
final class CatalogServiceFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'label' => fake()->words(3, true), 'amount' => 42500];
    }

    public function forOrganization(Organization $organization): self
    {
        return $this->state(fn (): array => ['organization_id' => $organization->id]);
    }
}
