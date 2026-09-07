<?php

declare(strict_types=1);

namespace App\UseCases\Chores;

use App\Contracts\Repositories\ChoreRepositoryInterface;
use App\Data\ChoreData;
use App\Enums\ChoreStatus;
use App\Models\Chore;
use App\Models\User;
use App\Notifications\ChoreActionedNotification;

/** Ticking a chore off moves it to Completed; un-ticking sends it back to Today. */
class ToggleChoreUseCase
{
    public function __construct(private readonly ChoreRepositoryInterface $chores) {}

    public function execute(Chore $chore, User $actor): ChoreData
    {
        $target = $chore->status === ChoreStatus::Done
            ? ChoreStatus::Today
            : ChoreStatus::Done;

        $updated = $this->chores->moveTo($chore, $target);

        $this->notifyInterestedParties($updated, $actor);

        return ChoreData::fromModel($updated);
    }

    /** Tells the assignee and/or creator when someone else actions their chore, without notifying either twice. */
    private function notifyInterestedParties(Chore $chore, User $actor): void
    {
        $notified = [$actor->id];

        foreach ([$chore->assignee, $chore->creator] as $recipient) {
            if ($recipient !== null && ! in_array($recipient->id, $notified, true)) {
                $recipient->notify(new ChoreActionedNotification($chore, $actor));
                $notified[] = $recipient->id;
            }
        }
    }
}
