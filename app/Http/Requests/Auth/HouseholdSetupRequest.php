<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Data\Input\HouseholdSetupInputData;
use App\Data\Input\InviteInputData;
use App\Enums\HouseholdRoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class HouseholdSetupRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:32'],
            // The owner takes one of the places.
            'invites' => ['array', 'max:'.(config('plans.max_members') - 1)],
            'invites.*.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'invites.*.role' => ['required', Rule::enum(HouseholdRoleEnum::class)],
            'guardian_confirmed' => ['boolean'],
        ];
    }

    /**
     * Under-18s join only with a parent or guardian's agreement (Terms of Service).
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $invitesMinors = collect($this->array('invites'))
                    ->contains(fn ($invite) => HouseholdRoleEnum::tryFrom((string) ($invite['role'] ?? ''))?->isForMinors() === true);

                if ($invitesMinors && ! $this->boolean('guardian_confirmed')) {
                    $validator->errors()->add('guardian_confirmed', 'Confirm you\'re their parent or guardian, or have their permission, to add someone as a Teen or Child.');
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $maxMembers = config('plans.max_members');

        return [
            'invites.max' => 'You can invite up to '.($maxMembers - 1)." people — a household holds {$maxMembers}, including you.",
        ];
    }

    public function toData(): HouseholdSetupInputData
    {
        /** @var list<array{email: string, role: string}> $invites */
        $invites = array_values($this->validated('invites', []));

        return new HouseholdSetupInputData(
            name: (string) $this->validated('name'),
            invites: array_map(fn (array $invite) => new InviteInputData($invite['email'], HouseholdRoleEnum::from($invite['role'])), $invites),
        );
    }
}
