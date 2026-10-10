<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ChoreStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveChoreRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ChoreStatusEnum::class)],
        ];
    }
}
