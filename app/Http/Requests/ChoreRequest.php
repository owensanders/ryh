<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\CurrentHouseholdHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChoreRequest extends FormRequest
{
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
        $assignedTo = $this->input('assigned_to');

        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'assigned_to' => match (true) {
                $assignedTo === null, $assignedTo === '' => null,
                is_numeric($assignedTo) => (int) $assignedTo,
                default => $assignedTo,
            },
        ]);
    }
}
