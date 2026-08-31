<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contract;
use App\Models\ContractService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractService>
 */
final class ContractServiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $contract = Contract::factory()->create();

        return [
            'organization_id' => $contract->organization_id,
            'contract_id' => $contract->id,
            'label' => 'Déneigement entrée',
            'amount' => fake()->randomElement([32500, 42500, 55000, 65000]),
            'position' => 1,
        ];
    }

    public function forContract(Contract $contract): self
    {
        return $this->state(fn (): array => [
            'organization_id' => $contract->organization_id,
            'contract_id' => $contract->id,
        ]);
    }

    public function driveway(): self
    {
        return $this->state(fn (): array => [
            'label' => 'Déneigement entrée',
            'amount' => fake()->randomElement([32500, 42500, 55000]),
            'position' => 1,
        ]);
    }

    public function walkway(): self
    {
        return $this->state(fn (): array => [
            'label' => 'Déneigement stationnement',
            'amount' => fake()->randomElement([12500, 15000, 20000]),
            'position' => 2,
        ]);
    }

    public function deIcing(): self
    {
        return $this->state(fn (): array => [
            'label' => 'Déglaçage',
            'amount' => fake()->randomElement([7500, 10000]),
            'position' => 3,
        ]);
    }

    /**
     * Position the service after existing ones for its contract.
     */
    public function nextPosition(): self
    {
        return $this->state(function (array $attributes): array {
            /** @var string|int|null $contractId */
            $contractId = $attributes['contract_id'] ?? null;

            /** @var int|null $highestPosition */
            $highestPosition = ContractService::query()
                ->where('contract_id', $contractId)
                ->max('position');

            return [
                'position' => $highestPosition === null ? 1 : $highestPosition + 1,
            ];
        });
    }
}
