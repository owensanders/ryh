<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\Input\CalendarEventInputData;
use App\Enums\PaletteEnum;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CalendarEventRequest extends FormRequest
{
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
            'colour' => ['nullable', Rule::enum(PaletteEnum::class)],
            'attendees' => ['array'],
            // Scoped to the household so an event can't be pinned on an outsider.
            'attendees.*' => [
                'integer',
                Rule::exists('household_user', 'user_id')->where('household_id', $this->user()?->current_household_id)->where('pending', false),
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

    public function toData(): CalendarEventInputData
    {
        $isAllDay = (bool) $this->validated('is_all_day', false);
        // `ends_at` is excluded from validation when all-day, so it is absent then.
        $endsAt = $isAllDay ? null : $this->validated('ends_at');

        return new CalendarEventInputData(
            title: trim((string) $this->validated('title')),
            startsAt: CarbonImmutable::parse((string) $this->validated('starts_at')),
            endsAt: $endsAt !== null ? CarbonImmutable::parse((string) $endsAt) : null,
            isAllDay: $isAllDay,
            location: $this->validated('location'),
            notes: $this->validated('notes'),
            colour: PaletteEnum::tryFrom((string) $this->validated('colour')) ?? PaletteEnum::Mint,
            attendeeIds: array_values(array_map(intval(...), $this->validated('attendees', []))),
        );
    }
}
