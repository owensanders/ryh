<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\Input\ChoreInputData;
use App\Enums\ChoreStatusEnum;
use App\Models\Chore;
use App\Models\Household;
use App\Models\User;
use Illuminate\Support\Collection;

class ChoreRepository
{
    /** @return Collection<int, Chore> */
    public function allFor(Household $household): Collection
    {
        return $household->chores()
            ->with('assignee')
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    public function moveTo(Chore $chore, ChoreStatusEnum $status): Chore
    {
        $chore->update(['status' => $status]);

        return $chore->refresh();
    }

    public function create(Household $household, ChoreInputData $chore, ChoreStatusEnum $status, User $creator): Chore
    {
        $position = $household->chores()->where('status', $status)->max('position') + 1;

        return $household->chores()->create([
            'name' => $chore->name,
            'assigned_to' => $chore->assignedTo,
            'created_by' => $creator->id,
            'status' => $status,
            'position' => $position,
        ]);
    }

    public function update(Chore $chore, ChoreInputData $changes): Chore
    {
        $chore->update(['name' => $changes->name, 'assigned_to' => $changes->assignedTo]);

        return $chore->refresh();
    }

    /** @return Collection<int, Chore> Assigned chores still to do, with their assignee. */
    public function outstandingAssigned(Household $household): Collection
    {
        return $household->chores()
            ->with('assignee')
            ->whereNotNull('assigned_to')
            ->where('status', ChoreStatusEnum::Today)
            ->get();
    }

    public function delete(Chore $chore): void
    {
        $chore->delete();
    }

    /** Deletes the chores assigned to this member in this household — their other households keep theirs. */
    public function deleteAssignedTo(User $member, Household $household): void
    {
        $member->assignedChores()->where('household_id', $household->id)->delete();
    }
}
