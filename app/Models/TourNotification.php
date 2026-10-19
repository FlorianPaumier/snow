<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonInterface;
use Database\Factories\TourNotificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property-read string $organization_id
 * @property-read string $tour_id
 * @property-read string $client_id
 * @property-read string|null $recipient_key
 * @property-read string $event
 * @property-read string $locale
 * @property-read string $status
 * @property-read string|null $reason
 * @property-read CarbonInterface|null $sent_at
 */
#[Fillable(['organization_id', 'tour_id', 'client_id', 'recipient_key', 'event', 'locale', 'status', 'reason', 'sent_at'])]
final class TourNotification extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<TourNotificationFactory> */
    use HasFactory;

    protected $attributes = ['status' => 'pending'];

    /** @return array<string, string> */
    public function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }
}
