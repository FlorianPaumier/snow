<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ClientType;
use App\Models\Client;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
final class ClientFactory extends Factory
{
    /**
     * Next sequence number per client type, seeded from the database maximum
     * so batched creations do not collide on the unique client number.
     *
     * @var array<string, int>
     */
    private array $sequences = [];

    /**
     * Define the model's default state.
     *
     * The client number mirrors the production GenerateClientNumber logic
     * (max-based per-type sequence, zero-padded) so factory-made clients are
     * indistinguishable from action-created ones.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'clientNumber' => $this->nextClientNumber(),
            'type' => ClientType::CLIENT_INDIVIDUAL,
            'companyName' => null,
            'firstName' => fake()->firstName(),
            'lastName' => fake()->lastName(),
            'address_street' => fake()->streetAddress(),
            'address_city' => fake()->city(),
            'address_postal_code' => fake()->postcode(),
            'address_country' => fake()->country(),
            'phoneDay' => fake()->phoneNumber(),
            'cellPhone' => null,
            'email' => fake()->unique()->safeEmail(),
            'remark' => null,
        ];
    }

    public function individual(): self
    {
        return $this->state(fn (): array => [
            'type' => ClientType::CLIENT_INDIVIDUAL,
        ]);
    }

    public function company(): self
    {
        return $this->state(fn (): array => [
            'type' => ClientType::CLIENT_COMPANY,
            'companyName' => fake()->company(),
        ]);
    }

    public function forOrganization(Organization $organization): self
    {
        return $this->state(fn (): array => [
            'organization_id' => $organization->id,
        ]);
    }

    public function withoutEmail(): self
    {
        return $this->state(fn (): array => [
            'email' => null,
        ]);
    }

    /**
     * Derive the next client number, e.g. "individual-0007".
     *
     * The sequence is max-based rather than count-based so numbers are never
     * reused after a client is deleted, and it is bumped in memory per call
     * so batched makes (which all run before the first insert) stay unique.
     */
    private function nextClientNumber(ClientType $type = ClientType::CLIENT_INDIVIDUAL): string
    {
        $prefix = sprintf('%s-', $type->value);

        if (! array_key_exists($type->value, $this->sequences)) {
            $highest = Client::query()->withoutGlobalScopes()
                ->where('clientNumber', 'like', sprintf('%s%%', $prefix))
                ->max('clientNumber');

            $this->sequences[$type->value] = is_string($highest)
                ? (int) mb_substr($highest, mb_strlen($prefix)) + 1
                : 1;
        }

        return sprintf('%s%04d', $prefix, $this->sequences[$type->value]++);
    }
}
