<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentSlot;
use App\Models\Contract;
use App\Models\ContractService;
use Illuminate\Support\Facades\DB;

final readonly class UpdateContract
{
    public function __construct(private FlushOrganizationCache $flushCache) {}

    /**
     * Syncs the contract's services (kept rows updated, removed rows deleted,
     * new rows created; positions re-derived from the submitted order) and
     * updates payment amounts for the unpaid slots only — an installment
     * already marked paid is locked until it is unmarked.
     *
     * @param  array{clientId: string, season: string, services: list<array{id?: int, label: string, amount: int}>, payments: array<string, int>}  $attributes
     */
    public function handle(Contract $contract, array $attributes): void
    {
        DB::transaction(function () use ($contract, $attributes): void {
            $contract->update([
                'client_id' => $attributes['clientId'],
                'season' => $attributes['season'],
            ]);

            $this->syncServices($contract, $attributes['services']);

            /** @var array<int, PaymentSlot>  $unpaidSlots indexed by payment id */
            $unpaidSlots = $contract->payments()
                ->where('paid', false)
                ->pluck('slot', 'id')
                ->all();

            foreach ($unpaidSlots as $paymentId => $slot) {
                $contract->payments()
                    ->whereKey($paymentId)
                    ->update(['amount' => $attributes['payments'][$slot->value] ?? 0]);
            }
        });

        $this->flushCache->handle($contract->organization_id);
    }

    /**
     * @param  list<array{id?: int, label: string, amount: int}>  $services
     */
    private function syncServices(Contract $contract, array $services): void
    {
        $keptIds = [];

        foreach ($services as $position => $service) {
            if (isset($service['id']) && $service['id'] !== 0) {
                ContractService::query()
                    ->whereKey($service['id'])
                    ->where('contract_id', $contract->id)
                    ->update([
                        'label' => $service['label'],
                        'amount' => $service['amount'],
                        'position' => $position + 1,
                    ]);

                $keptIds[] = $service['id'];

                continue;
            }

            $created = ContractService::query()->create([
                'contract_id' => $contract->id,
                'label' => $service['label'],
                'amount' => $service['amount'],
                'position' => $position + 1,
            ]);

            $keptIds[] = $created->id;
        }

        $contract->services()
            ->whereNotIn('id', $keptIds)
            ->delete();
    }
}
