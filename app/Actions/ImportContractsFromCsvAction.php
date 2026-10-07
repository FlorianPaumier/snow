<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentSlot;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ImportContractsFromCsvAction
{
    public function __construct(private ValidateContractCsvAction $validateCsv, private CreateContract $createContract) {}

    /** @param array<string, string|null> $mapping */
    public function handle(string $csv, array $mapping): int
    {
        $rows = $this->validateCsv->handle($csv, $mapping)['rows'];
        if ($rows === [] || array_filter($rows, fn (array $row): bool => $row['errors'] !== []) !== []) {
            throw ValidationException::withMessages(['file' => __('statuses.contracts_import_invalid')]);
        }

        DB::transaction(function () use ($rows): void {
            foreach ($rows as $row) {
                $values = $row['values'];
                $client = Client::query()->where('clientNumber', $values['clientNumber'])->firstOrFail();
                $this->createContract->handle([
                    'client' => $client,
                    'season' => $values['season'],
                    'services' => [['label' => $values['serviceLabel'], 'amount' => (int) $values['serviceAmount']]],
                    'payments' => [
                        PaymentSlot::SLOT_DEPOSIT->value => ['amount' => (int) $values['depositAmount'], 'dueDate' => $values['depositDueDate'] ?: null],
                        PaymentSlot::SLOT_FIRST_INSTALLMENT->value => ['amount' => (int) $values['firstAmount'], 'dueDate' => $values['firstDueDate'] ?: null],
                        PaymentSlot::SLOT_SECOND_INSTALLMENT->value => ['amount' => (int) $values['secondAmount'], 'dueDate' => $values['secondDueDate'] ?: null],
                        PaymentSlot::SLOT_BALANCE->value => ['amount' => (int) $values['balanceAmount'], 'dueDate' => $values['balanceDueDate'] ?: null],
                    ],
                ]);
            }
        });

        return count($rows);
    }
}
