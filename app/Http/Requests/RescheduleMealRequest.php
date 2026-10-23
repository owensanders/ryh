<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class RescheduleMealRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'planned_on' => ['required', 'date'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'planned_on.required' => 'Choose a day to move the meal to.',
        ];
    }

    public function plannedOn(): CarbonImmutable
    {
        return CarbonImmutable::parse((string) $this->validated('planned_on'));
    }
}
