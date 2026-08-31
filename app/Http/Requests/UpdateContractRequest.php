<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Models\Contract;
use App\Rules\UniqueContractSeason;
use App\ValueObjects\Season;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
                    $this->string('clientId')->value(),
                    $contract->id,
                ),
            ],

            'services' => ['required', 'array', 'min:1'],
            'services.*.id' => ['nullable', 'integer'],
            'services.*.label' => ['required', 'string', 'max:255'],
            'services.*.amount' => ['required', 'integer', 'min:0', 'max:10000000'],

            'payments' => ['required', 'array'],
            ...$this->paymentAmountRules(),
        ];
    }

    /**
     * The installments must cover exactly the total of the services, and a
     * slot already marked paid is locked — its amount cannot change until
     * the payment is unmarked...
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $contract = $this->route('contract');
        assert($contract instanceof Contract);

        $validator->after(function (\Illuminate\Validation\Validator $validator) use ($contract): void {
            /** @var mixed $services */
            $services = $this->input('services', []);
            /** @var mixed $payments */
            $payments = $this->input('payments', []);

            // Malformed shapes already fail the declared array rules; skip
            // the totals here so a TypeError does not turn validation into
            // a 500...
            if (! is_array($services) || ! is_array($payments)) {
                return;
            }

            $servicesTotal = array_sum(array_map((fn (array $service): int => (int) $service['amount']), $services));
            $paymentsTotal = array_sum(array_map((fn (mixed $amount): int => (int) $amount), $payments));

            if ($servicesTotal !== $paymentsTotal) {
                $validator->errors()->add('payments', 'Le total des versements doit correspondre au total des services.');
            }

            /** @var array<int, \App\Models\Payment>  $paidPayments */
            $paidPayments = $contract->payments()
                ->where('paid', true)
                ->get();

            foreach ($paidPayments as $paidPayment) {
                $slot = $paidPayment->slot->value;
                $storedAmount = $paidPayment->amount;
                $submitted = $this->input("payments.{$slot}", $storedAmount);
                $submittedAmount = is_numeric($submitted) ? (int) $submitted : $storedAmount;

                if ($submittedAmount !== $storedAmount) {
                    $validator->errors()->add("payments.{$slot}", 'Ce versement est encaissé et son montant ne peut plus être modifié.');
                }
            }
        });
    }

    /**
     * @return array<string, array<mixed>>
     */
    private function paymentAmountRules(): array
    {
        $rules = [];

        foreach (PaymentSlot::cases() as $slot) {
            $rules["payments.{$slot->value}"] = ['present', 'integer', 'min:0', 'max:10000000'];
        }

        return $rules;
    }
}
