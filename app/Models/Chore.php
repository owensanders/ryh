<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ChoreStatus;
use Database\Factories\ChoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $household_id
 * @property int|null $assigned_to
 * @property int|null $created_by
 * @property string $name
 * @property ChoreStatus $status
 * @property int $position
 * @property User|null $assignee
 * @property User|null $creator
 */
class Chore extends Model
{
    /** @use HasFactory<ChoreFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['household_id', 'assigned_to', 'created_by', 'name', 'status', 'position'];

    protected function casts(): array
    {
        return ['status' => ChoreStatus::class];
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
