<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Enums\NotificationCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePushOptOutsRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'opt_outs' => ['present', 'array'],
            'opt_outs.*' => ['distinct', Rule::enum(NotificationCategory::class)],
        ];
    }

    /** @return list<NotificationCategory> */
    public function optOuts(): array
    {
        return array_values(array_map(NotificationCategory::from(...), $this->validated('opt_outs')));
    }
}
