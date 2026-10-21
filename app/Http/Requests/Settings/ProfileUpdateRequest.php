<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Models\User;
use App\Traits\NormalisesInputTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    use NormalisesInputTrait;

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()?->id),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => $this->trimmed('name'), 'email' => $this->trimmed('email')]);
    }
}
