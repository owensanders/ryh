<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAccountRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'password' => ['required', 'current_password'],
            'confirm_household_deletion' => ['sometimes', 'boolean'],
        ];
    }

    /** A sole owner deleting their account deletes their shared households too; they have to say so. */
    public function confirmsHouseholdDeletion(): bool
    {
        return (bool) $this->validated('confirm_household_deletion', false);
    }
}
