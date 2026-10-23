<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Following an emailed invite to a household. The link is signed (the route
 * checks), and only the person it was sent to may use it: signed in as
 * anyone else, it's refused.
 */
class AcceptHouseholdInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->id === (int) $this->query('member');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'household' => ['required', 'integer'],
            'member' => ['required', 'integer'],
        ];
    }

    public function householdId(): int
    {
        return (int) $this->validated('household');
    }
}
