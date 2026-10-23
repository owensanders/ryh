<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Inputs\PasswordResetInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class NewPasswordRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function toInput(): PasswordResetInput
    {
        return PasswordResetInput::from($this->validated());
    }
}
