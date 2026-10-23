<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Inputs\LeaveHouseholdInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveHouseholdRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'household_id' => [
                'required',
                'integer',
                Rule::exists('household_user', 'household_id')->where('user_id', $this->user()?->id),
            ],
            'confirm_household_deletion' => ['sometimes', 'boolean'],
        ];
    }

    public function toInput(): LeaveHouseholdInput
    {
        return LeaveHouseholdInput::from($this->validated());
    }
}
