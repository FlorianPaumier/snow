<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreTourRequest extends FormRequest
{
    use TourInput {
        rules as private tourRules;
    }

    public function authorize(): bool
    {
        return $this->user()?->can('manage-tours') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [...$this->tourRules(), 'scheduled_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today']];
    }
}
