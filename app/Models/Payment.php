<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentSlot;
use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonInterface;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read string $organization_id
 * @property-read Organization $organization
 * @property-read PaymentSlot $slot
 * @property-read int $amount
 * @property-read bool $paid
 * @property-read PaymentMethod|null $method
 * @property-read CarbonInterface|null $paid_at
 * @property-read string $contract_id
 * @property-read Contract $contract
 */
final class Payment extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    public $timestamps = false;

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
            'amount' => 'integer',
            'paid' => 'boolean',
            'method' => PaymentMethod::class,
            'paid_at' => 'datetime',
            'contract_id' => 'string',
        ];
    }
}
