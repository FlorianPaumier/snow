<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DispatchTourTemplatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-tours') ?? false;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'request_key' => ['required', 'uuid'],
            'scheduled_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'template_ids' => ['required', 'array', 'list', 'min:1', 'max:50'],
            'template_ids.*' => ['required', 'uuid', 'distinct'],
        ];
    }

    /** @return array{request_key: string, scheduled_date: string, template_ids: list<string>} */
    public function dispatchData(): array
    {
        /** @var array{request_key: string, scheduled_date: string, template_ids: list<string>} $data */
        $data = $this->validated();

        return $data;
    }
}
