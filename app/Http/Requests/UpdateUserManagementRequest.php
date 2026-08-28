<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class UpdateUserManagementRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->route('user');
        assert($user instanceof User);

        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'max:255',
                'email',
                Rule::unique(User::class)->ignore($user->id),
            ],

            'password' => [
                'nullable',
                'confirmed',
                Password::defaults(),
            ],

            'role' => ['required', 'string', Rule::in([
                User::ROLE_ADMIN,
                User::ROLE_WORKER,
                User::ROLE_USER,
            ])],
        ];
    }
}
