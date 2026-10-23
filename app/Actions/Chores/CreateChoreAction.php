<?php

declare(strict_types=1);

namespace App\Actions\Chores;

use App\Enums\ChoreStatusEnum;
use App\Inputs\ChoreInput;
use App\Models\Chore;
use App\Models\Household;
use App\Models\User;
use App\Notifications\ChoreAssignedNotification;

class CreateChoreAction
{
    public function handle(Household $household, ChoreInput $input, User $actor): Chore
    {
        // New chores always start on the Today board, after what's already there.
        $chore = $household->chores()->create([
            'name' => $input->name,
            'assigned_to' => $input->assignedTo,
            'created_by' => $actor->id,
            'status' => ChoreStatusEnum::Today,
            'position' => (int) $household->chores()->where('status', ChoreStatusEnum::Today)->max('position') + 1,
        ]);

        if ($chore->assigned_to !== null && $chore->assigned_to !== $actor->id) {
            $chore->assignee?->notify(new ChoreAssignedNotification($household, $chore, $actor));
        }

        return $chore;
    }
}
