<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Rules\UniqueContractSeason;
use App\ValueObjects\Season;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ContractInput
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
                // Malformed shapes (arrays) fall back to a missing client id,
                // which the clientId rules reject as a 422 instead of a 500...
                new UniqueContractSeason(
                    is_string($this->input('clientId')) ? $this->input('clientId') : null,
                ),
            ],

            'services' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'services.*' => ['required', 'array:label,amount'],
            'services.*.label' => ['required', 'string', 'max:255'],
            'services.*.amount' => ['required', 'integer', 'min:0', 'max:10000000'],

            'payments' => ['required', 'array:deposit,first,second,balance'],
            ...$this->paymentRules(),
        ];
    }

    /**
     * The installments must cover exactly the total of the services...
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
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
