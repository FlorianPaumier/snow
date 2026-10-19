<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SaveCatalogServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-contracts') ?? false;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['label' => ['required', 'string', 'max:255'], 'amount' => ['required', 'integer', 'min:0', 'max:10000000']];
    }

    /** @return array{label: string, amount: int} */
    public function serviceData(): array
    {
        /** @var array{label: string, amount: int} $data */
        $data = $this->validated();

        return $data;
    }
}
