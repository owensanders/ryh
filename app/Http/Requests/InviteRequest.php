<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\HouseholdRoleEnum;
use App\Traits\NormalisesInputTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteRequest extends FormRequest
{
    use NormalisesInputTrait;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::enum(HouseholdRoleEnum::class)],
            // Under-18s join only with a parent or guardian's agreement (Terms of Service).
            'guardian_confirmed' => ['accepted_if:role,teen,child'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.required' => 'Add an email to invite someone',
            'guardian_confirmed.accepted_if' => 'Confirm you\'re their parent or guardian, or have their permission, to add someone as a Teen or Child.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => $this->trimmed('email')]);
    }
}
