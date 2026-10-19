<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SendTourUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-tours') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['event' => ['required', Rule::in(['started', 'finished'])], 'confirmed' => ['accepted']];
    }
}
