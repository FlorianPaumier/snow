<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateTourOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-tours') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'client_ids' => ['required', 'array', 'list', 'min:1', 'max:500'],
            'client_ids.*' => ['required', 'string', 'distinct:strict'],
        ];
    }
}
