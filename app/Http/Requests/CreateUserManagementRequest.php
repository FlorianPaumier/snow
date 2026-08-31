<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class CreateUserManagementRequest extends FormRequest
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
                Rule::unique(User::class),
            ],

            'password' => [
                'required',
                'confirmed',
                Password::defaults(),
            ],

            'role' => ['required', 'string', Rule::enum(UserRole::class)],
        ];
    }
}
