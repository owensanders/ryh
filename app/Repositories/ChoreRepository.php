<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\ChoreRepositoryInterface;
use App\Enums\ChoreStatus;
use App\Models\Chore;
use App\Models\Household;
use App\Models\User;
use Illuminate\Support\Collection;

class ChoreRepository implements ChoreRepositoryInterface
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

    public function moveTo(Chore $chore, ChoreStatus $status): Chore
    {
        $chore->update(['status' => $status]);

        return $chore->refresh();
    }

    /** @param array<string, mixed> $attributes */
    public function create(Household $household, array $attributes): Chore
    {
        $position = $household->chores()->where('status', $attributes['status'])->max('position') + 1;

        return $household->chores()->create([...$attributes, 'position' => $position]);
    }

    /** @param array<string, mixed> $attributes */
    public function update(Chore $chore, array $attributes): Chore
    {
        $chore->update($attributes);

        return $chore->refresh();
    }

    public function delete(Chore $chore): void
    {
        $chore->delete();
    }

    public function deleteAssignedTo(User $member, Household $household): void
    {
        $member->assignedChores()->where('household_id', $household->id)->delete();
    }
}
