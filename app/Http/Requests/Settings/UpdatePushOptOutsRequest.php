<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Enums\NotificationCategoryEnum;
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
            'opt_outs.*' => ['distinct', Rule::enum(NotificationCategoryEnum::class)],
        ];
    }

    /** @return list<NotificationCategoryEnum> */
    public function optOuts(): array
    {
        return array_values(array_map(NotificationCategoryEnum::from(...), $this->validated('opt_outs')));
    }
}
