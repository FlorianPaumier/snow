<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ClientType;
use App\Exceptions\PlanLimitExceeded;
use App\Models\Client;
use App\Models\Organization;
use App\ValueObjects\Address;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateClient
{
    /**
     * Attempts allowed when racing another request for the same client
     * number: the retry recomputes the max-based sequence, so a couple of
     * concurrent writers resolve without failing either one.
     */
    private const int MAX_NUMBER_ATTEMPTS = 3;

    public function __construct(
        private GenerateClientNumber $generateClientNumber,
        private GetPlanLimits $getPlanLimits,
        private FlushOrganizationCache $flushCache,
    ) {}

    /**
     * @param  array{type: ClientType|string, clientNumber?: string|null, companyName?: string|null, firstName: string, lastName: string, email: string, phoneDay?: string|null, cellPhone?: string|null, addressStreet: string, addressCity: string, addressPostalCode: string, addressCountry: string, remark?: string|null}  $attributes
     */
    public function handle(array $attributes): Client
    {
        $client = DB::transaction(function () use ($attributes): Client {
            $type = $attributes['type'] instanceof ClientType
                ? $attributes['type']
                : ClientType::from($attributes['type']);

            $organizationId = Auth::user()?->organization_id;

            $this->assertWithinClientLimit($organizationId);

            return $this->createWithNumberRetry($attributes, $type, $organizationId);
        });

        $this->flushCache->handle($client->organization_id);

        return $client;
    }

    /**
     * Create the client, recomputing the client number when a concurrent
     * request claimed the same one first: the max-based generator reads the
     * committed rows again and yields the next free slot, staying within the
     * composite unique (organization_id, clientNumber).
     *
     * Each attempt runs in its own savepoint: on PostgreSQL a failed insert
     * poisons the surrounding transaction unless the statement is isolated,
     * which would make the retry — and the caller's transaction — unusable.
     *
     * @param  array{type: ClientType|string, clientNumber?: string|null, companyName?: string|null, firstName: string, lastName: string, email: string, phoneDay?: string|null, cellPhone?: string|null, addressStreet: string, addressCity: string, addressPostalCode: string, addressCountry: string, remark?: string|null}  $attributes
     */
    private function createWithNumberRetry(array $attributes, ClientType $type, ?string $organizationId): Client
    {
        for ($attempt = 1; ; $attempt++) {
            $number = filled($attributes['clientNumber'] ?? null) ? $attributes['clientNumber'] : $this->generateClientNumber->handle($type, $organizationId);

            try {
                return DB::transaction(fn (): Client => Client::query()->create([
                    'organization_id' => $organizationId,
                    'clientNumber' => $number,
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
                ]));
            } catch (UniqueConstraintViolationException $exception) {
                throw_if(filled($attributes['clientNumber'] ?? null) || $attempt >= self::MAX_NUMBER_ATTEMPTS, $exception);
            }
        }
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
        $organization = Organization::query()->withoutGlobalScopes()->findOrFail($organizationId);

        $limits = $this->getPlanLimits->handle($organization);

        if ($limits['maxClients'] === null) {
            return;
        }

        // Anonymized skeletons are kept for accountability only; they must
        // not consume the organization's plan slots...
        $clientCount = Client::query()->withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->whereNull('anonymized_at')
            ->count();

        if ($clientCount >= $limits['maxClients']) {
            /** @var array{plan: string, maxClients: int} $limits */
            throw new PlanLimitExceeded(
                __('statuses.plan_limit', ['plan' => $limits['plan'], 'count' => $limits['maxClients'], 'resource' => __('statuses.resource_clients')]),
            );
        }
    }
}
