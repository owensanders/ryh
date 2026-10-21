<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\Input\ChoreInputData;
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

    public function toData(): ChoreInputData
    {
        return new ChoreInputData(
            name: trim((string) $this->validated('name')),
            assignedTo: $this->filled('assigned_to') ? (int) $this->validated('assigned_to') : null,
        );
    }
}
