<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\ValidEmail;
use App\Rules\ValidOrganizationInviteCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class CreateUserRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'max:255',
                'email',
                new ValidEmail,
                Rule::unique(User::class),
            ],
            'organization' => ['required', 'string', 'max:255'],
            // Sign-up is invite-only: a code emailed by the operator must
            // accompany the registration, bound to this email...
            'invite_code' => ['required', 'string', new ValidOrganizationInviteCode],
            'password' => [
                'required',
                'confirmed',
                Password::defaults(),
            ],
            'terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'terms.accepted' => __(
                'You must accept the Terms of Service and the Privacy Policy to create an account.',
            ),
        ];
    }
}
