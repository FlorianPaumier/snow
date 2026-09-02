<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractService;
use App\Models\Payment;
use App\ValueObjects\Season;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

final class ContractSeeder extends Seeder
{
    public function run(): void
    {
        // Oldest season first so payment dates and contract creation order
        // follow the natural timeline...
        $seasons = [
            Season::current()->previous()->previous(),
            Season::current()->previous(),
            Season::current(),
        ];

        /** @var Collection<int, Client> $clients */
        $clients = Client::query()->withoutGlobalScopes()->get();

        // Clients join progressively across the seasons so the dashboard's
        // evolution charts show growth (oldest: ~40%, then ~70%, then all)...
        $clientCountsBySeason = [
            max(4, (int) floor($clients->count() * 0.4)),
            max(7, (int) floor($clients->count() * 0.7)),
            $clients->count(),
        ];

        foreach ($seasons as $seasonIndex => $season) {
            foreach ($clients->take($clientCountsBySeason[$seasonIndex]) as $client) {
                // updateOrCreate keeps the seeder idempotent: one contract per
                // client per season (the unique constraint enforces it too)...
                $contract = Contract::query()->withoutGlobalScopes()->updateOrCreate(
                    ['season' => $season->label, 'client_id' => $client->id],
                    ['season' => $season->label, 'client_id' => $client->id, 'organization_id' => $client->organization_id],
                );

                ContractService::query()->withoutGlobalScopes()->where('contract_id', $contract->id)->delete();
                Payment::query()->withoutGlobalScopes()->where('contract_id', $contract->id)->delete();

                $services = [
                    ['label' => 'Déneigement entrée', 'amount' => 42500, 'position' => 1],
                    ['label' => 'Déneigement stationnement', 'amount' => 15000, 'position' => 2],
                ];

                foreach ($services as $service) {
                    ContractService::query()->withoutGlobalScopes()->create([
                        ...$service,
                        'organization_id' => $client->organization_id,
                        'contract_id' => $contract->id,
                    ]);
                }

                $this->seedPayments($contract);
            }
        }
    }

    private function seedPayments(Contract $contract): void
    {
        $installments = [
            ['slot' => 'deposit', 'amount' => 15000],
            ['slot' => 'first', 'amount' => 20000],
            ['slot' => 'second', 'amount' => 20000],
            ['slot' => 'balance', 'amount' => 2500],
        ];

        // Vary how many installments each client has paid so both paid and
        // unpaid rows exist across the seasons...
        $paidCount = fake()->numberBetween(0, count($installments));

        foreach ($installments as $index => $installment) {
            $paid = $index < $paidCount;

            Payment::query()->withoutGlobalScopes()->create([
                'organization_id' => $contract->organization_id,
                'contract_id' => $contract->id,
                'slot' => $installment['slot'],
                'amount' => $installment['amount'],
                'paid' => $paid,
                'method' => $paid ? 'virement' : null,
                'paid_at' => $paid ? now()->subDays(30 - $index) : null,
            ]);
        }
    }
}
