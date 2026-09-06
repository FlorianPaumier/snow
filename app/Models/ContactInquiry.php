<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactInquiryStatus;
use Carbon\CarbonInterface;
use Database\Factories\ContactInquiryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Pre-auth marketing lead submitted through the public contact form.
 * Deliberately not organization-scoped: inquiries exist before any
 * organization or user account does.
 *
 * @property-read string $id
 * @property-read string $name
 * @property-read string $email
 * @property-read string|null $company
 * @property-read string|null $fleetSize
 * @property-read string $message
 * @property-read ContactInquiryStatus $status
 * @property-read CarbonInterface|null $sent_at
 * @property-read CarbonInterface|null $failed_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
#[Fillable([
    'name',
    'email',
    'company',
    'fleetSize',
    'message',
    'status',
    'sent_at',
    'failed_at',
])]
final class ContactInquiry extends Model
{
    /** @use HasFactory<ContactInquiryFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * Mirrors the migration default so a freshly built (unsaved) model
     * reports the same delivery state as one loaded from the database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => ContactInquiryStatus::STATUS_PENDING,
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'name' => 'string',
            'email' => 'string',
            'company' => 'string',
            'fleetSize' => 'string',
            'message' => 'string',
            'status' => ContactInquiryStatus::class,
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
