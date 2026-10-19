<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractService;
use App\Models\Quote;
use App\Models\User;
use App\ValueObjects\Season;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final readonly class PrepareSeasonRenewals
{
    public function __construct(private CreateQuote $create) {}

    /**
     * @param  list<string>  $contractIds
     * @return array{created: int, existing: int, anonymized: int}
     */
    public function handle(User $admin, string $sourceSeason, string $targetSeason, array $contractIds): array
    {
        Gate::forUser($admin)->authorize('manage-contracts');
        $this->validateSeasons($sourceSeason, $targetSeason);

        return DB::transaction(function () use ($admin, $sourceSeason, $targetSeason, $contractIds): array {
            $contracts = Contract::query()->where('organization_id', $admin->organization_id)
                ->where('season', $sourceSeason)->whereIn('id', $contractIds)
                ->with(['services', 'payments'])->orderBy('id')->lockForUpdate()->get();
            abort_unless($contracts->count() === count($contractIds), 404);
            $result = ['created' => 0, 'existing' => 0, 'anonymized' => 0];

            foreach ($contracts as $contract) {
                $client = Client::query()->where('organization_id', $admin->organization_id)->lockForUpdate()->findOrFail($contract->client_id);
                if ($client->isAnonymized()) {
                    $result['anonymized']++;

                    continue;
                }

                $hasContract = Contract::query()->where('organization_id', $admin->organization_id)
                    ->where('client_id', $client->id)->where('season', $targetSeason)->lockForUpdate()->first() !== null;
                $hasQuote = Quote::query()->where('organization_id', $admin->organization_id)
                    ->where('client_id', $client->id)->where('season', $targetSeason)
                    ->where('status', '!=', 'replaced')->lockForUpdate()->first() !== null;
                if ($hasContract || $hasQuote) {
                    $result['existing']++;

                    continue;
                }

                $payments = $contract->payments->keyBy('slot.value');
                $quote = $this->create->handle($admin, [
                    'clientId' => $client->id,
                    'season' => $targetSeason,
                    'services' => array_values($contract->services->map(fn (ContractService $service): array => [
                        'label' => $service->label, 'amount' => $service->amount,
                    ])->all()),
                    'payments' => collect(PaymentSlot::cases())->mapWithKeys(fn (PaymentSlot $slot): array => [
                        $slot->value => ['amount' => $payments->get($slot->value)->amount ?? 0, 'dueDate' => null],
                    ])->all(),
                ]);
                $quote->forceFill(['source_contract_id' => $contract->id])->save();
                $result['created']++;
            }

            return $result;
        }, 3);
    }

    private function validateSeasons(string $source, string $target): void
    {
        try {
            $next = Season::fromString($source)->next()->label;
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['sourceSeason' => __('renewals.invalid_season')]);
        }

        $current = Season::current();
        throw_unless(in_array($source, [$current->previous()->label, $current->label], true), ValidationException::withMessages(['sourceSeason' => __('renewals.invalid_season')]));
        throw_unless($next === $target, ValidationException::withMessages(['targetSeason' => __('renewals.invalid_season')]));
    }
}
