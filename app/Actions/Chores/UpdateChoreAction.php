<?php

declare(strict_types=1);

namespace App\Actions\Chores;

use App\Inputs\ChoreInput;
use App\Models\Chore;
use App\Models\Household;
use App\Models\User;
use App\Notifications\ChoreAssignedNotification;
use App\Notifications\ChoreEditedNotification;
use App\Support\RecipientsHelper;

class UpdateChoreAction
{
    public function handle(Household $household, Chore $chore, ChoreInput $input, User $actor): Chore
    {
        $previousAssignee = $chore->assigned_to;

        $chore->update(['name' => $input->name, 'assigned_to' => $input->assignedTo]);
        $updated = $chore->refresh();

        $reassigned = $updated->assigned_to !== null && $updated->assigned_to !== $previousAssignee;

        if ($reassigned && $updated->assigned_to !== $actor->id) {
            $updated->assignee?->notify(new ChoreAssignedNotification($household, $updated, $actor));
        }

        if (! $reassigned) {
            $this->notifyInterestedParties($household, $updated, $actor);
        }

        return $updated;
    }

    /** Tells the assignee and/or creator when someone else edits their chore, without notifying either twice. */
    private function notifyInterestedParties(Household $household, Chore $chore, User $actor): void
    {
        foreach (RecipientsHelper::except($actor, [$chore->assignee, $chore->creator]) as $recipient) {
            $recipient->notify(new ChoreEditedNotification($household, $chore, $actor));
        }
    }
}
