<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateClientRouteNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-clients') ?? false;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['enabled' => ['required', 'boolean']];
    }
}
