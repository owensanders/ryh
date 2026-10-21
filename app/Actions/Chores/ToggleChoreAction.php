<?php

declare(strict_types=1);

namespace App\Actions\Chores;

use App\Data\ChoreData;
use App\Enums\ChoreStatusEnum;
use App\Models\Chore;
use App\Models\User;
use App\Notifications\ChoreActionedNotification;
use App\Repositories\ChoreRepository;
use App\Support\RecipientsHelper;

/** Ticking a chore off moves it to Completed; un-ticking sends it back to Today. */
class ToggleChoreAction
{
    public function __construct(private readonly ChoreRepository $chores) {}

    public function handle(Chore $chore, User $actor): ChoreData
    {
        $target = $chore->status === ChoreStatusEnum::Done
            ? ChoreStatusEnum::Today
            : ChoreStatusEnum::Done;

        $updated = $this->chores->moveTo($chore, $target);

        $this->notifyInterestedParties($updated, $actor);

        return ChoreData::fromModel($updated);
    }

    /** Tells the assignee and/or creator when someone else actions their chore, without notifying either twice. */
    private function notifyInterestedParties(Chore $chore, User $actor): void
    {
        foreach (RecipientsHelper::except($actor, [$chore->assignee, $chore->creator]) as $recipient) {
            $recipient->notify(new ChoreActionedNotification($chore, $actor));
        }
    }
}
