<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\AddressCast;
use App\Enums\ClientType;
use App\Models\Concerns\BelongsToOrganization;
use App\ValueObjects\Address;
use Carbon\CarbonInterface;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;

/**
 * @property-read string $id
 * @property-read string $organization_id
 * @property-read Organization $organization
 * @property-read string $clientNumber
 * @property-read ClientType $type
 * @property-read string|null $companyName
 * @property-read string $lastName
 * @property-read string $firstName
 * @property-read Address $address
 * @property-read string|null $phoneDay
 * @property-read string|null $cellPhone
 * @property-read string|null $email
 * @property-read string|null $remark
 * @property-read CarbonInterface|null $anonymized_at
 * @property-read string|null $anonymized_by
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class Client extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    use HasUuids;
    use Notifiable;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'organization_id' => 'string',
            'clientNumber' => 'string',
            'type' => ClientType::class,
            'companyName' => 'string',
            'lastName' => 'string',
            'firstName' => 'string',
            'address' => AddressCast::class,
            'phoneDay' => 'string',
            'cellPhone' => 'string',
            'email' => 'string',
            'remark' => 'string',
            'anonymized_at' => 'datetime',
            'anonymized_by' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Once anonymized (Quebec Law 25), the identity is irreversibly erased
     * and the record can never carry personal data again.
     */
    public function isAnonymized(): bool
    {
        return $this->anonymized_at !== null;
    }

    /**
     * The client's display name: the company name for companies, the
     * first and last name for individuals. Anonymized records show a
     * neutral placeholder — their identity fields no longer exist.
     */
    public function displayName(): string
    {
        if ($this->isAnonymized()) {
            return __('clients.anonymized_name');
        }

        if ($this->type === ClientType::CLIENT_COMPANY && $this->companyName !== null) {
            return $this->companyName;
        }

        return sprintf('%s %s', $this->firstName, $this->lastName);
    }

    /**
     * @return HasMany<Contract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * @return HasOne<Contract, $this>
     */
    public function lastContract(): HasOne
    {
        return $this->hasOne(Contract::class)->latestOfMany();
    }

    /**
     * Clients whose identity has been erased; their financial skeleton
     * remains for accountability.
     *
     * @param  Builder<Client>  $query
     * @return Builder<Client>
     */
    public function scopeWhereAnonymized(Builder $query): Builder
    {
        return $query->whereNotNull('anonymized_at');
    }

    /**
     * @param  Builder<Client>  $query
     * @return Builder<Client>
     */
    public function scopeWhereNotAnonymized(Builder $query): Builder
    {
        return $query->whereNull('anonymized_at');
    }
}
