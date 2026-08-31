<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contract;
use Illuminate\Support\Collection;

final readonly class GetRevenueEvolution
{
    /**
     * Revenue per season, oldest first, for the evolution chart: total
     * revenue (contract services) and collected (paid installments).
     *
     * @return Collection<int, array{season: string, revenue: int, collected: int, outstanding: int}>
     */
    public function handle(): Collection
    {
        $revenuePerContract = '(select coalesce(sum(amount), 0) from contract_services where contract_services.contract_id = contracts.id)';
        $collectedPerContract = '(select coalesce(sum(amount), 0) from payments where payments.contract_id = contracts.id and payments.paid = 1)';

        /** @var Collection<int, object{season: string, revenue: int, collected: int}> $rows */
        $rows = Contract::query()
            ->toBase()
            ->selectRaw(sprintf(
                'season, sum(%s) as revenue, sum(%s) as collected',
                $revenuePerContract,
                $collectedPerContract,
            ))
            ->groupBy('season')
            ->orderBy('season')
            ->get();

        return $rows->map(fn (object $row): array => [
            'season' => $row->season,
            'revenue' => (int) $row->revenue,
            'collected' => (int) $row->collected,
            'outstanding' => (int) $row->revenue - (int) $row->collected,
        ]);
    }
}
