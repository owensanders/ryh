<?php

declare(strict_types=1);

namespace App\Actions\Chores;

use App\Models\Chore;
use App\Models\Household;
use App\Models\User;
use App\Notifications\ChoreAssignedNotification;
use App\Notifications\ChoreEditedNotification;
use App\Support\RecipientsHelper;

class UpdateChoreAction
{
    /** @param  array<string, mixed>  $data  ChoreRequest's validated input: name, assigned_to. */
    public function handle(Household $household, Chore $chore, array $data, User $actor): Chore
    {
        $previousAssignee = $chore->assigned_to;

        $chore->update(['name' => $data['name'], 'assigned_to' => $data['assigned_to']]);
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
