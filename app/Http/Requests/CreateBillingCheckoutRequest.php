<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateBillingCheckoutRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        /** @var array<string, mixed> $plans */
        $plans = config('plans');

        return [
            'plan' => ['required', 'string', Rule::in(array_keys($plans))],
            'interval' => ['required', 'string', Rule::in(['monthly', 'annual'])],
        ];
    }
}
