<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Validation\Rule;

trait TourInput
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'worker_id' => ['required', 'uuid', Rule::exists('users', 'id')->where('organization_id', $organizationId)->where('role', UserRole::ROLE_WORKER->value)],
            'departure_address' => ['nullable', 'string', 'max:1000'],
            'arrival_address' => ['nullable', 'string', 'max:1000'],
            'client_ids' => ['required', 'array', 'list', 'min:1', 'max:500'],
            'client_ids.*' => ['required', 'uuid', 'distinct', Rule::exists('clients', 'id')->where('organization_id', $organizationId)->whereNull('anonymized_at')],
        ];
    }
}
