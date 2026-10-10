<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ChoreStatus;
use Database\Factories\ChoreFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $household_id
 * @property int|null $assigned_to
 * @property int|null $created_by
 * @property string $name
 * @property ChoreStatus $status
 * @property Carbon|null $completed_at
 * @property int $position
 * @property User|null $assignee
 * @property User|null $creator
 */
class Chore extends Model
{
    /** @use HasFactory<ChoreFactory> */
    use HasFactory, MassPrunable;

    /** How long a completed chore stays on the board before the nightly prune clears it. */
    public const int KEEP_COMPLETED_DAYS = 30;

    /** @var list<string> */
    protected $fillable = ['household_id', 'assigned_to', 'created_by', 'name', 'status', 'position'];

    protected function casts(): array
    {
        return [
            'status' => ChoreStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // However a chore changes status — toggled, moved, created straight
        // into Completed — its completion time follows.
        static::saving(function (Chore $chore): void {
            if ($chore->isDirty('status') || ! $chore->exists) {
                $chore->completed_at = $chore->status === ChoreStatus::Done
                    ? ($chore->completed_at ?? now())
                    : null;
            }
        });
    }

    /** @return Builder<static> */
    public function prunable(): Builder
    {
        return static::query()
            ->where('status', ChoreStatus::Done)
            ->where('completed_at', '<', now()->subDays(self::KEEP_COMPLETED_DAYS));
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
