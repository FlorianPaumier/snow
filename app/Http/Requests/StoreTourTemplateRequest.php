<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreTourTemplateRequest extends FormRequest
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
        return $this->tourRules();
    }

    /** @return array{name: string, worker_id: string, departure_address?: string|null, arrival_address?: string|null, client_ids: list<string>} */
    public function templateData(): array
    {
        /** @var array{name: string, worker_id: string, departure_address?: string|null, arrival_address?: string|null, client_ids: list<string>} $data */
        $data = $this->validated();

        return $data;
    }
}
