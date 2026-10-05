<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Enums\HouseholdRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HouseholdSetupRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:32'],
            // The owner takes one of the places.
            'invites' => ['array', 'max:'.(config('plans.max_members') - 1)],
            'invites.*.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'invites.*.role' => ['required', Rule::enum(HouseholdRole::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $maxMembers = config('plans.max_members');

        return [
            'invites.max' => 'You can invite up to '.($maxMembers - 1)." people — a household holds {$maxMembers}, including you.",
        ];
    }

    /** @return array{name: string, size: string|null, invites: list<array{email: string, role: string}>} */
    public function householdAttributes(): array
    {
        return [
            'name' => (string) $this->validated('name'),
            'size' => $this->validated('size'),
            'invites' => $this->validated('invites', []),
        ];
    }
}
