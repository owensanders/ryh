<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Palette;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Collection;

/**
 * @property int $id
 * @property int $household_id
 * @property int|null $created_by
 * @property string $title
 * @property \Illuminate\Support\Carbon $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property bool $is_all_day
 * @property string|null $location
 * @property string|null $notes
 * @property \App\Enums\Palette $colour
 * @property Collection<int, User> $attendees
 * @property User|null $creator
 */
class CalendarEvent extends Model
{
    /** @use HasFactory<\Database\Factories\CalendarEventFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['household_id', 'created_by', 'title', 'starts_at', 'ends_at', 'is_all_day', 'location', 'notes', 'colour'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_all_day' => 'boolean',
            'colour' => Palette::class,
        ];
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
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

    /** 'All day', '18:00', or '18:00–19:30'. */
    public function timeLabel(): string
    {
        if ($this->is_all_day) {
            return 'All day';
        }

        $start = $this->starts_at->format('H:i');

        return $this->ends_at !== null ? "{$start}–{$this->ends_at->format('H:i')}" : $start;
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
