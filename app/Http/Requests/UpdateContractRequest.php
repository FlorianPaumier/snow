<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Payment;
use App\Rules\UniqueContractSeason;
use App\ValueObjects\Season;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateContractRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $seasons = [
            Season::current()->previous()->label,
            Season::current()->label,
            Season::current()->next()->label,
        ];

        $contract = $this->route('contract');
        assert($contract instanceof Contract);

        return [
            'clientId' => [
                'required',
                'uuid',
                Rule::exists(Client::class, 'id'),
            ],

            'season' => [
                'required',
                'string',
                Rule::in($seasons),
                new UniqueContractSeason(
                    // Malformed shapes (arrays) fall back to a missing client
                    // id, rejected as a 422 by the clientId rules...
                    is_string($this->input('clientId')) ? $this->input('clientId') : null,
                    $contract->id,
                ),
            ],

            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['required', 'array'],
            'services.*.id' => ['nullable', 'integer'],
            'services.*.label' => ['required', 'string', 'max:255'],
            'services.*.amount' => ['required', 'integer', 'min:0', 'max:10000000'],

            'payments' => ['required', 'array'],
            ...$this->paymentRules(),
        ];
    }

    /**
     * The installments must cover exactly the total of the services, and a
     * slot already marked paid is locked — its amount cannot change until
     * the payment is unmarked...
     */
    public function withValidator(Validator $validator): void
    {
        $contract = $this->route('contract');
        assert($contract instanceof Contract);

        $validator->after(function (Validator $validator) use ($contract): void {
            $services = $this->input('services', []);
            $payments = $this->input('payments', []);

            // Malformed shapes already fail the declared array rules; skip
            // the totals here so a TypeError does not turn validation into
            // a 500...
            if ($validator->errors()->isNotEmpty() || ! is_array($services) || ! is_array($payments)) {
                return;
            }

            /** @var list<array{amount: int|string}> $serviceRows */
            $serviceRows = array_values($services);
            /** @var list<array{amount: int|string}> $paymentRows */
            $paymentRows = array_values(array_filter($payments, is_array(...)));

            $servicesTotal = array_sum(array_map(fn (array $service): int => (int) $service['amount'], $serviceRows));
            $paymentsTotal = array_sum(array_map(fn (array $payment): int => (int) $payment['amount'], $paymentRows));

            if ($servicesTotal !== $paymentsTotal) {
                $validator->errors()->add('payments', __('contracts.totals_mismatch'));
            }

            /** @var array<int, Payment> $paidPayments */
            $paidPayments = $contract->payments()
                ->where('paid', true)
                ->get();

            foreach ($paidPayments as $paidPayment) {
                $slot = $paidPayment->slot->value;
                $storedAmount = $paidPayment->amount;
                $submitted = $this->input('payments.'.$slot.'.amount', $storedAmount);
                $submittedAmount = is_numeric($submitted) ? (int) $submitted : $storedAmount;

                if ($submittedAmount !== $storedAmount) {
                    $validator->errors()->add('payments.'.$slot.'.amount', __('contracts.slot_amount_locked'));
                }
            }
        });
    }

    /**
     * @return array<string, array<mixed>>
     */
    private function paymentRules(): array
    {
        $rules = [];

        foreach (PaymentSlot::cases() as $slot) {
            $rules['payments.'.$slot->value] = ['present', 'array'];
            $rules['payments.'.$slot->value.'.amount'] = ['required', 'integer', 'min:0', 'max:10000000'];
            $rules['payments.'.$slot->value.'.dueDate'] = ['nullable', 'date'];
        }

        return $rules;
    }
}
