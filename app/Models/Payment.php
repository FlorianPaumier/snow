<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentSlot;
use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonInterface;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

/**
 * @property-read CarbonInterface|null $stripe_checkout_started_at
 * @property-read string|null $stripe_checkout_key
 * @property-read string|null $stripe_session_id
 * @property-read string|null $stripe_payment_intent_id
 * @property-read string|null $stripe_status
 * @property-read int $stripe_reversed_amount
 * @property-read int $id
 * @property-read string $organization_id
 * @property-read Organization $organization
 * @property-read PaymentSlot $slot
 * @property-read CarbonInterface|null $due_date
 * @property-read int $amount
 * @property-read bool $paid
 * @property-read PaymentMethod|null $method
 * @property-read CarbonInterface|null $paid_at
 * @property-read CarbonInterface|null $last_reminder_at
 * @property-read int $automated_reminders_sent
 * @property-read string $contract_id
 * @property-read Contract $contract
 */
#[WithoutTimestamps]
#[Fillable([
    'organization_id',
    'contract_id',
    'slot',
    'amount',
    'due_date',
    'paid',
    'method',
    'paid_at',
    'last_reminder_at',
    'automated_reminders_sent',
])]
final class Payment extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * Mirrors the migration defaults so a freshly built (unsaved) model
     * reports the same reminder state as one loaded from the database...
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'stripe_reversed_amount' => 0,
        'due_date' => null,
        'last_reminder_at' => null,
        'automated_reminders_sent' => 0,
    ];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'organization_id' => 'string',
            'slot' => PaymentSlot::class,
            'due_date' => 'date',
            'stripe_checkout_started_at' => 'datetime',
            'stripe_reversed_amount' => 'integer',
            'amount' => 'integer',
            'paid' => 'boolean',
            'method' => PaymentMethod::class,
            'paid_at' => 'datetime',
            'last_reminder_at' => 'datetime',
            'automated_reminders_sent' => 'integer',
            'contract_id' => 'string',
        ];
    }

    public function collectedAmount(): int
    {
        return $this->paid ? $this->amount - $this->stripe_reversed_amount : 0;
    }

    public function receiptAvailable(): bool
    {
        return $this->paid && $this->stripe_reversed_amount === 0 && $this->stripe_status !== 'disputed';
    }

    public function paymentUrl(?CarbonInterface $startedAt = null): string
    {
        return URL::temporarySignedRoute('connected-payments.show', ($startedAt ?? now())->copy()->addDays(30), [
            'organization' => $this->organization_id, 'payment' => $this->id,
        ]);
    }
}
