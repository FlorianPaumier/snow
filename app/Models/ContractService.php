<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ContractServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read string $organization_id
 * @property-read Organization $organization
 * @property-read string $label
 * @property-read int $amount
 * @property-read int $position
 * @property-read string $contract_id
 * @property-read Contract $contract
 */
#[WithoutTimestamps]
#[Fillable([
    'organization_id',
    'contract_id',
    'label',
    'amount',
    'position',
])]
final class ContractService extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<ContractServiceFactory> */
    use HasFactory;

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
            'label' => 'string',
            'amount' => 'integer',
            'position' => 'integer',
            'contract_id' => 'string',
        ];
    }
}
