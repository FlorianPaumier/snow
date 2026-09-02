<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contract;
use Illuminate\Validation\ValidationException;

final readonly class DeleteContract
{
    public function __construct(private FlushOrganizationCache $flushCache) {}

    public function handle(Contract $contract): void
    {
        throw_if(
            $contract->payments()->where('paid', true)->exists(),
            ValidationException::withMessages([
                'contract' => __('contracts.delete_with_paid'),
            ]),
        );

        $organizationId = $contract->organization_id;

        $contract->delete();

        $this->flushCache->handle($organizationId);
    }
}
