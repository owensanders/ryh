<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Inputs\ChoreInput;
use App\Support\CurrentHouseholdHelper;
use App\Traits\NormalisesInputTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChoreRequest extends FormRequest
{
    use NormalisesInputTrait;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('household_user', 'user_id')->where('household_id', CurrentHouseholdHelper::id($this))->where('pending', false),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Give the chore a name.',
        ];
    }

    /** Validated as it'll be stored: a trimmed name, and an id or null for "nobody". */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->trimmed('name'),
            'assigned_to' => $this->idOrNull('assigned_to'),
        ]);
    }

    public function toInput(): ChoreInput
    {
        return ChoreInput::from($this->validated());
    }
}
