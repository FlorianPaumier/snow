<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Quote;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreQuoteRequest extends FormRequest
{
    use ContractInput {
        rules as private contractRules;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $quote = $this->route('quote');
        $renewal = $quote instanceof Quote && $quote->source_contract_id !== null;

        return $renewal ? [...$this->contractRules(), 'renewalReviewed' => ['required', 'accepted']] : $this->contractRules();
    }

    /** @return array{clientId: string, season: string, services: list<array{label: string, amount: int}>, payments: array<string, array{amount: int, dueDate: string|null}>} */
    public function quoteData(): array
    {
        /** @var array{clientId: string, season: string, services: list<array{label: string, amount: int|string}>, payments: array<string, array{amount: int|string, dueDate: string|null}>} $data */
        $data = $this->validated();

        return [
            'clientId' => $data['clientId'], 'season' => $data['season'],
            'services' => array_map(fn (array $service): array => ['label' => $service['label'], 'amount' => (int) $service['amount']], $data['services']),
            'payments' => array_map(fn (array $payment): array => ['amount' => (int) $payment['amount'], 'dueDate' => $payment['dueDate'] ?? null], $data['payments']),
        ];
    }

    public function authorize(): bool
    {
        return $this->user()?->can('manage-contracts') ?? false;
    }
}
