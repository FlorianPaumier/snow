<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\TourTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read string $id
 * @property-read string $name
 * @property-read string|null $worker_id
 * @property-read User|null $worker
 * @property-read list<string> $client_ids
 * @property-read string|null $departure_address
 * @property-read string|null $arrival_address
 */
#[Fillable(['organization_id', 'name', 'worker_id', 'client_ids', 'departure_address', 'arrival_address'])]
final class TourTemplate extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<TourTemplateFactory> */
    use HasFactory;

    use HasUuids;

    /** @return BelongsTo<User, $this> */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    public function casts(): array
    {
        return ['client_ids' => 'array'];
    }
}
