<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ImportClientsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-clients') ?? false;
    }

    /** @return array<string, array<mixed>|string> */
    public function rules(): array
    {
        if ($this->input('mode') === 'import' && is_string($this->input('csv'))) {
            return [
                'csv' => ['required', 'string', 'max:6990507'],
                'mapping' => ['sometimes', 'array'],
                'mapping.*' => ['nullable', 'string', 'max:255'],
                'mode' => ['required', 'in:preview,import'],
            ];
        }

        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'mapping' => ['sometimes', 'array'],
            'mapping.*' => ['nullable', 'string', 'max:255'],
            'mode' => ['required', 'in:preview,import'],
        ];
    }
}
