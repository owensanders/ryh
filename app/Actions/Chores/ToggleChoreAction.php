<?php

declare(strict_types=1);

namespace App\Actions\Chores;

use App\Enums\ChoreStatusEnum;
use App\Models\Chore;
use App\Models\Household;
use App\Models\User;
use App\Notifications\ChoreActionedNotification;
use App\Support\RecipientsHelper;

/** Ticking a chore off moves it to Completed; un-ticking sends it back to Today. */
class ToggleChoreAction
{
    public function handle(Household $household, Chore $chore, User $actor): Chore
    {
        $target = $chore->status === ChoreStatusEnum::Done
            ? ChoreStatusEnum::Today
            : ChoreStatusEnum::Done;

        $chore->update(['status' => $target]);
        $updated = $chore->refresh();

        $this->notifyInterestedParties($household, $updated, $actor);

        return $updated;
    }

    /** Tells the assignee and/or creator when someone else actions their chore, without notifying either twice. */
    private function notifyInterestedParties(Household $household, Chore $chore, User $actor): void
    {
        foreach (RecipientsHelper::except($actor, [$chore->assignee, $chore->creator]) as $recipient) {
            $recipient->notify(new ChoreActionedNotification($household, $chore, $actor));
        }
    }
}
