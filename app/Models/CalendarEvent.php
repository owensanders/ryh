<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\UtcDateTimeCast;
use App\Enums\PaletteEnum;
use Carbon\CarbonImmutable;
use Database\Factories\CalendarEventFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $household_id
 * @property int|null $created_by
 * @property string $title
 * @property Carbon $starts_at
 * @property Carbon|null $ends_at
 * @property bool $is_all_day
 * @property string|null $location
 * @property string|null $notes
 * @property PaletteEnum $colour
 * @property Collection<int, User> $attendees
 * @property User|null $creator
 */
class CalendarEvent extends Model
{
    /** @use HasFactory<CalendarEventFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['household_id', 'created_by', 'title', 'starts_at', 'ends_at', 'is_all_day', 'location', 'notes', 'colour'];

    protected function casts(): array
    {
        return [
            'starts_at' => UtcDateTimeCast::class,
            'ends_at' => UtcDateTimeCast::class,
            'is_all_day' => 'boolean',
            'colour' => PaletteEnum::class,
        ];
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /**
     * Events that touch the window (two moments): they start before it ends, and either end
     * inside or after it, or (with no end time) start inside it. In date order.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function overlapping(Builder $query, CarbonImmutable $from, CarbonImmutable $to): void
    {
        // The columns hold UTC; a bound Carbon is written out in its own zone, unconverted.
        $from = $from->utc();
        $to = $to->utc();

        $query->where('starts_at', '<=', $to)
            ->where(function (Builder $query) use ($from): void {
                $query->where('ends_at', '>=', $from)
                    ->orWhere(fn (Builder $query) => $query->whereNull('ends_at')->where('starts_at', '>=', $from));
            })
            ->orderBy('starts_at')
            ->orderBy('id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The members this event is for. Empty means it is a whole-house event.
     *
     * @return BelongsToMany<User, $this>
     */
    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->orderBy('id');
    }

    /** When it starts on a clock in `$timezone` (the household's). It's stored as a UTC moment. */
    public function startsIn(string $timezone): CarbonImmutable
    {
        return $this->starts_at->toImmutable()->setTimezone($timezone);
    }

    /** When it ends on a clock in `$timezone`, if it has an end time. */
    public function endsIn(string $timezone): ?CarbonImmutable
    {
        return $this->ends_at?->toImmutable()->setTimezone($timezone);
    }

    /**
     * Over once its end has passed: its end time, or for an all-day event or
     * one with no end, the end of the day it starts on the household's clock.
     */
    public function hasEndedBy(CarbonImmutable $moment, string $timezone): bool
    {
        return ($this->endsIn($timezone) ?? $this->startsIn($timezone)->endOfDay())->lt($moment);
    }

    /** 'All day', '18:00', or '18:00–19:30', on the household's clock. */
    public function timeLabel(string $timezone): string
    {
        if ($this->is_all_day) {
            return 'All day';
        }

        $start = $this->startsIn($timezone)->format('H:i');
        $end = $this->endsIn($timezone);

        return $end !== null ? "{$start}–{$end->format('H:i')}" : $start;
    }

    /** First names of the attendees, or 'Everyone' when the event is for the whole house. */
    public function whoLabel(): string
    {
        if ($this->attendees->isEmpty()) {
            return 'Everyone';
        }

        return $this->attendees
            ->map(fn (User $member) => explode(' ', $member->name)[0])
            ->join(', ');
    }
}
