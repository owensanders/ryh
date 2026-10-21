<?php

declare(strict_types=1);

namespace App\Models\Builders;

use App\Enums\ChoreStatusEnum;
use App\Models\Chore;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * The chore queries more than one place needs. Reached through the household,
 * so they're already scoped to it: `$household->chores()->forBoard()->get()`.
 *
 * @extends Builder<Chore>
 */
class ChoreBuilder extends Builder
{
    /** In board order, with who each is assigned to. */
    public function forBoard(): self
    {
        return $this->with('assignee')->orderBy('position')->orderBy('id');
    }

    /** Assigned chores still to do, with their assignee. */
    public function outstandingAssigned(): self
    {
        return $this->with('assignee')
            ->whereNotNull('assigned_to')
            ->where('status', ChoreStatusEnum::Today);
    }

    public function assignedTo(User $member): self
    {
        return $this->where('assigned_to', $member->id);
    }

    /** Where a new chore goes in a column: after everything already in it. */
    public function nextPositionIn(ChoreStatusEnum $status): int
    {
        return (int) $this->clone()->where('status', $status)->max('position') + 1;
    }
}
