<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\ValueObjects\Season;
use Carbon\CarbonInterface;
use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;

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
#[Fillable([
    'organization_id',
    'client_id',
    'season',
    'created_at',
    'updated_at',
])]
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

    /**
     * The date the contract's season ends (June 30 of the season's end
     * year) — the anchor from which legal data-retention windows are
     * counted. Falls back to the creation date for malformed labels.
     */
    public function seasonEndDate(): CarbonInterface
    {
        try {
            $endYear = Season::fromString($this->season)->endYear;
        } catch (InvalidArgumentException) {
            $endYear = $this->created_at->year;
        }

        return Date::create($endYear, 6, 30, 23, 59, 59);
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
