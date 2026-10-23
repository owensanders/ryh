<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Inputs\PendingInviteInput;
use App\Inputs\RegistrationInput;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', Password::defaults()],
            'terms' => ['required', 'accepted'],
        ];
    }

    public function toInput(): RegistrationInput
    {
        return RegistrationInput::from($this->validated());
    }

    /** The household invite they opened the page through (ShowRegisterRequest), taken out of the session. */
    public function pendingInvite(): ?PendingInviteInput
    {
        $invite = $this->session()->pull(PendingInviteInput::SESSION_KEY);

        return $invite instanceof PendingInviteInput ? $invite : null;
    }
}
