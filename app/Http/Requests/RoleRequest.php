<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\HouseholdRoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::enum(HouseholdRoleEnum::class)],
            // Under-18s join only with a parent or guardian's agreement (Terms of Service).
            'guardian_confirmed' => ['accepted_if:role,teen,child'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'guardian_confirmed.accepted_if' => 'Confirm you\'re their parent or guardian, or have their permission, to add someone as a Teen or Child.',
        ];
    }
}
