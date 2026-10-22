<?php

declare(strict_types=1);

namespace App\Actions\Chores;

use App\Models\Chore;
use App\Models\Household;
use App\Models\User;
use App\Notifications\ChoreDeletedNotification;
use App\Support\RecipientsHelper;

class DeleteChoreAction
{
    public function handle(Household $household, Chore $chore, User $actor): void
    {
        $this->notifyInterestedParties($household, $chore, $actor);

        $chore->delete();
    }

    /** Tells the assignee and/or creator when someone else deletes their chore, without notifying either twice. */
    private function notifyInterestedParties(Household $household, Chore $chore, User $actor): void
    {
        foreach (RecipientsHelper::except($actor, [$chore->assignee, $chore->creator]) as $recipient) {
            $recipient->notify(new ChoreDeletedNotification($household, $chore, $actor));
        }
    }
}
