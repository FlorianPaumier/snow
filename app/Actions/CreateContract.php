<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractService;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CreateContract
{
    public function __construct(private FlushOrganizationCache $flushCache) {}

    /**
     * @param  array{client: Client, season: string, services: list<array{label: string, amount: int}>, payments: array<string, array{amount: int, dueDate: string|null}>}  $attributes
     */
    public function handle(array $attributes): Contract
    {
        throw_if(
            $attributes['client']->isAnonymized(),
            ValidationException::withMessages([
                'client' => __('clients.anonymized_no_new_contract'),
            ]),
        );

        $contract = DB::transaction(function () use ($attributes): Contract {
            // The caller resolves the client (scoped in HTTP contexts) so
            // the contract inherits its organization...
            $client = $attributes['client'];

            $contract = Contract::query()->create([
                'organization_id' => $client->organization_id,
                'client_id' => $client->id,
                'season' => $attributes['season'],
            ]);

            $this->createServices($contract, $attributes['services']);
            $this->createPayments($contract, $attributes['payments']);

            return $contract;
        });

        $this->flushCache->handle($contract->organization_id);

        return $contract;
    }

    /**
     * @param  list<array{label: string, amount: int}>  $services
     */
    private function createServices(Contract $contract, array $services): void
    {
        foreach ($services as $position => $service) {
            ContractService::query()->create([
                'organization_id' => $contract->organization_id,
                'contract_id' => $contract->id,
                'label' => $service['label'],
                'amount' => $service['amount'],
                'position' => $position + 1,
            ]);
        }
    }

    /**
     * @param  array<string, array{amount: int, dueDate: string|null}>  $payments
     */
    private function createPayments(Contract $contract, array $payments): void
    {
        foreach (PaymentSlot::cases() as $slot) {
            Payment::query()->create([
                'organization_id' => $contract->organization_id,
                'contract_id' => $contract->id,
                'slot' => $slot,
                'amount' => $payments[$slot->value]['amount'] ?? 0,
                'due_date' => $payments[$slot->value]['dueDate'] ?? null,
                'paid' => false,
                'method' => null,
                'paid_at' => null,
            ]);
        }
    }
}
