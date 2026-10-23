<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PaletteEnum;
use App\Models\CalendarEvent;
use App\Support\CurrentHouseholdHelper;
use App\Traits\NormalisesInputTrait;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CalendarEventRequest extends FormRequest
{
    use NormalisesInputTrait;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'starts_at' => ['required', 'date', $this->notInThePast(...)],
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
            'ends_at.after' => 'The end time must be after the start time.',
        ];
    }

    /**
     * An event that's over can't be changed; one still running can (CalendarEvent::hasEndedBy()).
     *
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $event = $this->route('event');

                if ($event instanceof CalendarEvent && $event->hasEndedBy(CarbonImmutable::now(), CurrentHouseholdHelper::household($this)->timezone)) {
                    $validator->errors()->add('starts_at', 'This event has already happened, so it can\'t be changed.');
                }
            },
        ];
    }

    /**
     * Before the household's today is refused, so nothing new lands in the
     * past. Keeping an event's start as it was is always fine: an event
     * already under way can still have its details changed. The form sends
     * times as the household's clock reads them.
     */
    private function notInThePast(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || strtotime($value) === false) {
            return;
        }

        $household = CurrentHouseholdHelper::household($this);
        $event = $this->route('event');

        if ($event instanceof CalendarEvent && $household->momentAt($value)->equalTo($event->starts_at)) {
            return;
        }

        if (CarbonImmutable::parse($value)->lt($household->today())) {
            $fail('The event can\'t be scheduled in the past.');
        }
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
