<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ContactInquiryFactory;
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
 * @property-read \Carbon\CarbonInterface $created_at
 * @property-read \Carbon\CarbonInterface $updated_at
 */
final class ContactInquiry extends Model
{
    /** @use HasFactory<ContactInquiryFactory> */
    use HasFactory;

    use HasUuids;

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
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
