<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ClientType;
use App\Exceptions\PlanLimitExceeded;
use App\Models\Client;
use App\Models\Organization;
use App\ValueObjects\Address;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateClient
{
    public function __construct(
        private GenerateClientNumber $generateClientNumber,
        private GetPlanLimits $getPlanLimits,
    ) {}

    /**
     * @param  array{type: ClientType|string, companyName?: string|null, firstName: string, lastName: string, email: string, phoneDay?: string|null, cellPhone?: string|null, addressStreet: string, addressCity: string, addressPostalCode: string, addressCountry: string, remark?: string|null}  $attributes
     */
    public function handle(array $attributes): Client
    {
        return DB::transaction(function () use ($attributes): Client {
            $type = $attributes['type'] instanceof ClientType
                ? $attributes['type']
                : ClientType::from($attributes['type']);

            $organizationId = Auth::user()?->organization_id;

            $this->assertWithinClientLimit($organizationId);

            return Client::query()->create([
                'organization_id' => $organizationId,
                'clientNumber' => $this->generateClientNumber->handle($type, $organizationId),
                'type' => $type,
                'companyName' => $attributes['companyName'] ?? null,
                'lastName' => $attributes['lastName'],
                'firstName' => $attributes['firstName'],
                'address' => new Address(
                    street: $attributes['addressStreet'],
                    city: $attributes['addressCity'],
                    postalCode: $attributes['addressPostalCode'],
                    country: $attributes['addressCountry'],
                ),
                'phoneDay' => $attributes['phoneDay'] ?? null,
                'cellPhone' => $attributes['cellPhone'] ?? null,
                'email' => $attributes['email'],
                'remark' => $attributes['remark'] ?? null,
            ]);
        });
    }

    /**
     * Enforce the organization's plan limit on clients, blocking the create
     * with an upgrade prompt when reached.
     */
    private function assertWithinClientLimit(?string $organizationId): void
    {
        if ($organizationId === null) {
            return;
        }

        /** @var Organization $organization */
        $organization = Organization::withoutGlobalScopes()->findOrFail($organizationId);

        $limits = $this->getPlanLimits->handle($organization);

        if ($limits['maxClients'] === null) {
            return;
        }

        $clientCount = Client::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->count();

        if ($clientCount >= $limits['maxClients']) {
            throw new PlanLimitExceeded(
                sprintf('Your %s plan allows %d clients. Upgrade to add more.', $limits['plan'], $limits['maxClients']),
            );
        }
    }
}
