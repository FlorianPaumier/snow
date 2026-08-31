<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ClientType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateClientRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::enum(ClientType::class)],

            'companyName' => [
                'nullable',
                'string',
                'max:255',
                'required_if:type,'.ClientType::CLIENT_COMPANY->value,
            ],

            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],

            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],

            // At least one phone number must be provided...
            'phoneDay' => ['nullable', 'string', 'max:255', 'required_without:cellPhone'],
            'cellPhone' => ['nullable', 'string', 'max:255', 'required_without:phoneDay'],

            'addressStreet' => ['required', 'string', 'max:255'],
            'addressCity' => ['required', 'string', 'max:255'],
            'addressPostalCode' => ['required', 'string', 'max:255'],
            'addressCountry' => ['required', 'string', 'max:255'],

            'remark' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
