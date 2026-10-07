<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonInterface;
use Database\Factories\TourFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read string $id
 * @property-read string $organization_id
 * @property-read string|null $worker_id
 * @property-read User|null $worker
 * @property-read string $name
 * @property-read CarbonInterface $scheduled_date
 * @property-read string|null $departure_address
 * @property-read string|null $arrival_address
 * @property-read list<array{id: string, name: string, address: string, remark: string|null}> $stops
 * @property-read CarbonInterface|null $optimized_at
 * @property-read CarbonInterface|null $started_at
 * @property-read CarbonInterface|null $finished_at
 */
#[Fillable(['organization_id', 'worker_id', 'name', 'scheduled_date', 'departure_address', 'arrival_address', 'stops', 'optimized_at', 'started_at', 'finished_at'])]
final class Tour extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<TourFactory> */
    use HasFactory;

    use HasUuids;

    /** @return BelongsTo<User, $this> */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /** @return array<string, string> */
    public function casts(): array
    {
        return [
            'scheduled_date' => 'immutable_date',
            'stops' => 'array',
            'optimized_at' => 'immutable_datetime',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
        ];
    }
}
