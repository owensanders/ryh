<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HouseTimezoneRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'timezone' => ['required', 'string', 'timezone:all'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'timezone.timezone' => 'Pick a time zone from the list.',
        ];
    }
}
