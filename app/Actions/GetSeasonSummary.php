<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contract;
use App\Support\TenantCache;
use App\ValueObjects\Season;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final readonly class GetSeasonSummary
{
    public function __construct(private TenantCache $cache) {}

    /**
     * Summary of a season's contracts: total revenue, collected and
     * outstanding amounts, and the number of contracts.
     *
     * @return array{revenue: int, collected: int, outstanding: int, contracts_count: int}
     */
    public function handle(Season $season): array
    {
        /** @var array{revenue: int, collected: int, outstanding: int, contracts_count: int} */
        return $this->cache->remember(
            'season-summary',
            [$season->label],
            fn (): array => $this->compute($season),
            TenantCache::TTL_LONG,
        );
    }

    /**
     * @return array{revenue: int, collected: int, outstanding: int, contracts_count: int}
     */
    private function compute(Season $season): array
    {
        $seasonContracts = fn (): Builder => Contract::query()->where('season', $season->label);

        $revenue = (int) $seasonContracts()
            ->join('contract_services', 'contract_services.contract_id', '=', 'contracts.id')
            ->toBase()
            ->sum('contract_services.amount');

        $collected = (int) $seasonContracts()
            ->join('payments', 'payments.contract_id', '=', 'contracts.id')
            ->where('payments.paid', true)
            ->toBase()
            ->sum(DB::raw('payments.amount - payments.stripe_reversed_amount'));
        $contractsCount = (int) $seasonContracts()->toBase()->count();

        return [
            'revenue' => $revenue,
            'collected' => $collected,
            'outstanding' => $revenue - $collected,
            'contracts_count' => $contractsCount,
        ];
    }
}
