<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Enums\HouseholdRoleEnum;
use App\Inputs\HouseholdSetupInput;
use App\Traits\NormalisesInputTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class HouseholdSetupRequest extends FormRequest
{
    use NormalisesInputTrait;

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
            // From the browser; one PHP doesn't know falls back to the UK rather than failing setup.
            'timezone' => ['nullable', 'string', 'max:64'],
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

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => $this->trimmed('name')]);
    }

    public function toInput(): HouseholdSetupInput
    {
        return HouseholdSetupInput::from($this->validated());
    }
}
