<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonInterface;
use Database\Factories\TourDispatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read string $id
 * @property-read string $request_key
 * @property-read CarbonInterface $scheduled_date
 * @property-read list<string> $template_ids
 */
#[Fillable(['organization_id', 'request_key', 'scheduled_date', 'template_ids'])]
final class TourDispatch extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<TourDispatchFactory> */
    use HasFactory;

    use HasUuids;

    /** @return array<string, string> */
    public function casts(): array
    {
        return ['scheduled_date' => 'immutable_date', 'template_ids' => 'array'];
    }
}
