<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contract;
use Illuminate\Support\Collection;

final readonly class GetSeasonRevenueRecap
{
    /**
     * Revenue recap for every season that has contracts: total revenue
     * (contract services), collected (paid installments) and outstanding
     * amounts, and the number of contracts, newest season first.
     *
     * @return Collection<int, array{season: string, revenue: int, collected: int, outstanding: int, contracts_count: int}>
     */
    public function handle(): Collection
    {
        $revenuePerContract = '(select coalesce(sum(amount), 0) from contract_services where contract_services.contract_id = contracts.id)';
        $collectedPerContract = '(select coalesce(sum(amount), 0) from payments where payments.contract_id = contracts.id and payments.paid = 1)';

        /** @var Collection<int, object{season: string, revenue: int, collected: int, contracts_count: int}> $rows */
        $rows = Contract::query()
            ->toBase()
            ->selectRaw(sprintf(
                'season, sum(%s) as revenue, sum(%s) as collected, count(*) as contracts_count',
                $revenuePerContract,
                $collectedPerContract,
            ))
            ->groupBy('season')
            ->orderByDesc('season')
            ->get();

        return $rows->map(fn (object $row): array => [
            'season' => $row->season,
            'revenue' => (int) $row->revenue,
            'collected' => (int) $row->collected,
            'outstanding' => (int) $row->revenue - (int) $row->collected,
            'contracts_count' => (int) $row->contracts_count,
        ]);
    }
}
