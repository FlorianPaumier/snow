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

final readonly class UpdateContract
{
    public function __construct(
        private DeleteStoredDocument $deleteDocument,
        private FlushOrganizationCache $flushCache,
    ) {}

    /**
     * Syncs the contract's services (kept rows updated, removed rows deleted,
     * new rows created; positions re-derived from the submitted order) and
     * updates payment amounts and due dates for the unpaid slots only — an
     * installment already marked paid is locked until it is unmarked.
     *
     * @param  array{clientId: string, season: string, services: list<array{id?: int, label: string, amount: int}>, payments: array<string, array{amount: int, dueDate: string|null}>}  $attributes
     */
    public function handle(Contract $contract, array $attributes): void
    {
        // Reassigning a contract onto an anonymized client would attach new
        // business activity to an identity that no longer exists...
        throw_if(
            Client::query()->findOrFail($attributes['clientId'])->isAnonymized(),
            ValidationException::withMessages([
                'client' => __('clients.anonymized_no_new_contract'),
            ]),
        );

        DB::transaction(function () use ($contract, $attributes): void {
            $contract = Contract::query()->lockForUpdate()->findOrFail($contract->id);
            $payments = $contract->payments()->lockForUpdate()->get();
            throw_if($payments->contains(fn (Payment $payment): bool => $payment->stripe_checkout_key !== null),
                ValidationException::withMessages(['contract' => __('connect.locked')]));
            $serviceIds = collect($attributes['services'])->pluck('id')->filter(fn (mixed $id): bool => $id !== null && $id !== 0);
            throw_if($serviceIds->count() !== $serviceIds->unique()->count()
                || $contract->services()->whereIn('id', $serviceIds)->count() !== $serviceIds->count(),
                ValidationException::withMessages(['services' => __('validation.exists', ['attribute' => 'services'])]));

            foreach ($payments as $payment) {
                if ($payment->paid && (int) ($attributes['payments'][$payment->slot->value]['amount'] ?? 0) !== $payment->amount) {
                    throw ValidationException::withMessages(['payments.'.$payment->slot->value.'.amount' => __('contracts.slot_amount_locked')]);
                }
            }

            $effectiveTotal = $payments->sum(fn (Payment $payment): int => $payment->paid ? $payment->amount : (int) ($attributes['payments'][$payment->slot->value]['amount'] ?? 0));
            throw_if($effectiveTotal !== array_sum(array_column($attributes['services'], 'amount'))
                || array_sum(array_column($attributes['services'], 'amount')) !== array_sum(array_column($attributes['payments'], 'amount')),
                ValidationException::withMessages(['payments' => __('contracts.totals_mismatch')]));

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
                    ->update([
                        'amount' => $attributes['payments'][$slot->value]['amount'] ?? 0,
                        'due_date' => $attributes['payments'][$slot->value]['dueDate'] ?? null,
                    ]);
            }
        });

        // The stored PDF mirrors the previous state of the contract; the
        // generate flow short-circuits while it exists, so it must go now
        // to be rebuilt from the updated data on the next request...
        $this->deleteDocument->handle(GenerateContractPdf::pdfPath($contract->id));

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
