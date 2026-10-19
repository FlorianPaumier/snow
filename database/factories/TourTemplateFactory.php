<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\TourTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TourTemplate> */
final class TourTemplateFactory extends Factory
{
    public function definition(): array
    {
        $worker = User::factory()->worker()->create();
        $client = Client::factory()->forOrganization($worker->organization)->create();

        return ['organization_id' => $worker->organization_id, 'worker_id' => $worker->id,
            'name' => fake()->streetName(), 'client_ids' => [$client->id]];
    }
}
