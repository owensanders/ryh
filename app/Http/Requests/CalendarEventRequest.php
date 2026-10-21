<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PaletteEnum;
use App\Support\CurrentHouseholdHelper;
use App\Traits\NormalisesInputTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CalendarEventRequest extends FormRequest
{
    use NormalisesInputTrait;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'starts_at' => ['required', 'date', 'after_or_equal:today'],
            // An all-day event has no end time, so a stale one must not block the save.
            'ends_at' => ['exclude_if:is_all_day,true', 'nullable', 'date', 'after:starts_at'],
            'is_all_day' => ['boolean'],
            'location' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'colour' => ['required', Rule::enum(PaletteEnum::class)],
            'attendees' => ['array'],
            // Scoped to the household so an event can't be pinned on an outsider.
            'attendees.*' => [
                'integer',
                Rule::exists('household_user', 'user_id')->where('household_id', CurrentHouseholdHelper::id($this))->where('pending', false),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'title.required' => 'Give the event a title.',
            'starts_at.after_or_equal' => 'The event can\'t be scheduled in the past.',
            'ends_at.after' => 'The end time must be after the start time.',
        ];
    }

    /** A trimmed title, mint when no colour is picked, and attendee ids as ints. */
    protected function prepareForValidation(): void
    {
        $attendees = $this->input('attendees', []);

        $this->merge([
            'title' => $this->trimmed('title'),
            'colour' => $this->filled('colour') ? $this->input('colour') : PaletteEnum::Mint->value,
            'attendees' => is_array($attendees)
                ? array_values(array_map(fn (mixed $id) => is_numeric($id) ? (int) $id : $id, $attendees))
                : $attendees,
        ]);
    }
}
