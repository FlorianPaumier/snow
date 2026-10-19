<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\ValueObjects\Season;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PrepareSeasonRenewalsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-contracts') ?? false;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        // shortcut: current/previous sources match quote forms; expand when historical renewals are needed.
        $current = Season::current();
        $source = $this->input('sourceSeason', $current->label);
        $target = $source === $current->previous()->label ? $current->label : $current->next()->label;

        return [
            'sourceSeason' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'string', Rule::in([$current->previous()->label, $current->label])],
            'targetSeason' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'string', Rule::in([$target])],
            'contractIds' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'array', 'list', 'min:1', 'max:100'],
            'contractIds.*' => ['required', 'uuid', 'distinct'],
        ];
    }

    public function sourceSeason(): string
    {
        /** @var string $source */
        $source = $this->validated('sourceSeason', Season::current()->label);

        return $source;
    }

    /** @return list<string> */
    public function contractIds(): array
    {
        /** @var list<string> $ids */
        $ids = $this->validated('contractIds');

        return $ids;
    }
}
