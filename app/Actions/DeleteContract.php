<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contract;
use Illuminate\Validation\ValidationException;

final readonly class DeleteContract
{
    public function handle(Contract $contract): void
    {
        throw_if(
            $contract->payments()->where('paid', true)->exists(),
            ValidationException::withMessages([
                'contract' => 'Ce contrat a des versements encaissés et ne peut pas être supprimé.',
            ]),
        );

        $contract->delete();
    }
}
