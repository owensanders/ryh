<?php

declare(strict_types=1);

namespace App\Actions\Chores;

use App\Data\ChoreData;
use App\Data\Input\ChoreInputData;
use App\Enums\ChoreStatusEnum;
use App\Models\Household;
use App\Models\User;
use App\Notifications\ChoreAssignedNotification;

class CreateChoreAction
{
    public function handle(Household $household, ChoreInputData $input, User $actor): ChoreData
    {
        // New chores always start on the Today board, after what's already there.
        $chore = $household->chores()->create([
            'name' => $input->name,
            'assigned_to' => $input->assignedTo,
            'created_by' => $actor->id,
            'status' => ChoreStatusEnum::Today,
            'position' => $household->chores()->nextPositionIn(ChoreStatusEnum::Today),
        ]);

        if ($chore->assigned_to !== null && $chore->assigned_to !== $actor->id) {
            $chore->assignee?->notify(new ChoreAssignedNotification($chore, $actor));
        }

        return ChoreData::fromModel($chore);
    }
}
