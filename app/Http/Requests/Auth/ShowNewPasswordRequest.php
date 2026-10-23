<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/** Opening the reset-password page from the emailed link, which carries the email address. */
class ShowNewPasswordRequest extends FormRequest
{
    /**
     * Nothing to validate: a malformed email is just not filled in.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function email(): string
    {
        $email = $this->query('email');

        return is_string($email) ? $email : '';
    }
}
