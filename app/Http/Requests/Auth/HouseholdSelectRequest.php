<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HouseholdSelectRequest extends FormRequest
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
        ];
    }

    public function householdId(): int
    {
        return (int) $this->validated('household_id');
    }
}
