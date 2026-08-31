<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonInterface;
use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

/**
 * @property-read string $id
 * @property-read string $organization_id
 * @property-read Organization $organization
 * @property-read string $season
 * @property-read string $client_id
 * @property-read Client $client
 * @property-read Collection<int, ContractService> $services
 * @property-read Collection<int, Payment> $payments
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 *
 * Computed by withSum() on the contracts index...
 * @property-read int|null $services_sum_amount
 * @property-read int|null $paid_sum
 */
final class Contract extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<ContractFactory> */
    use HasFactory;

    use HasUuids;
    use Notifiable;

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return HasMany<ContractService, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(ContractService::class)->orderBy('position');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function casts(): array
    {
        return [
            'id' => 'string',
            'organization_id' => 'string',
            'season' => 'string',
            'client_id' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
