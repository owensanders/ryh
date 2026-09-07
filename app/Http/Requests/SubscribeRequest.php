<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->currentRole()?->value === 'owner';
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'interval' => ['required', 'string', 'in:monthly,annual'],
        ];
    }
}
