<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Contract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
final class ContractFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * The client is created first so the contract can inherit its
     * organization; passing a persisted client via forClient() skips the
     * extra create.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $client = Client::factory()->create();

        return [
            'organization_id' => $client->organization_id,
            'season' => sprintf('%d-%d', now()->year, now()->year + 1),
            'client_id' => $client->id,
        ];
    }

    public function forClient(Client $client): self
    {
        return $this->state(fn (): array => [
            'organization_id' => $client->organization_id,
            'client_id' => $client->id,
        ]);
    }
}
