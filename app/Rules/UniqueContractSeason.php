<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Contract;
use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Fails when another contract already exists for the same client and season.
 *
 * A client signs exactly one contract per season; on update the current
 * contract is ignored so it can be edited freely. Applied to the season
 * attribute, with the client id injected by the form request.
 */
final readonly class UniqueContractSeason implements ValidationRule
{
    public function __construct(
        private ?string $clientId,
        private ?string $ignoreContractId = null,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $this->clientId === null) {
            return; // A missing client id is caught by the clientId rules...
        }

        $exists = Contract::query()
            ->where('client_id', $this->clientId)
            ->where('season', $value)
            ->when(
                $this->ignoreContractId !== null,
                fn (Builder $query): Builder => $query->whereKeyNot($this->ignoreContractId),
            )
            ->exists();

        if ($exists) {
            $fail(__('contracts.duplicate_season'));
        }
    }
}
