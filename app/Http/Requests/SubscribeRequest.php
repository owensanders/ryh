<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'interval' => ['required', 'string', 'in:monthly,annual'],
            // Where they started: the House tab returns there; otherwise the paywall.
            'from' => ['nullable', 'string', 'in:house'],
        ];
    }
}
