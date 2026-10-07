<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ImportContractsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-contracts') ?? false;
    }

    /** @return array<string, array<mixed>|string> */
    public function rules(): array
    {
        $rules = [
            'mapping' => ['required', 'array'],
            'mapping.*' => ['nullable', 'string', 'max:255'],
            'mode' => ['required', 'in:preview,import'],
        ];

        if ($this->input('mode') === 'import' && is_string($this->input('csv'))) {
            return [...$rules, 'csv' => ['required', 'string', 'max:6990507']];
        }

        return [...$rules, 'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']];
    }
}
