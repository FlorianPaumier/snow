<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SendContractEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-contracts') ?? false;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['contract_ids' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'contract_ids.*' => ['required', 'uuid', 'distinct']];
    }

    /** @return list<string> */
    public function contractIds(): array
    {
        /** @var list<string> $ids */
        $ids = $this->validated('contract_ids');

        return $ids;
    }
}
