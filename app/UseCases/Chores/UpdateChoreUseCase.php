<?php

declare(strict_types=1);

namespace App\UseCases\Chores;

use App\Contracts\Repositories\ChoreRepositoryInterface;
use App\Data\ChoreData;
use App\Models\Chore;
use App\Models\User;
use App\Notifications\ChoreAssignedNotification;
use App\Notifications\ChoreEditedNotification;

class UpdateChoreUseCase
{
    public function __construct(private readonly ChoreRepositoryInterface $chores) {}

    /** @param array<string, mixed> $attributes */
    public function execute(Chore $chore, array $attributes, User $actor): ChoreData
    {
        $previousAssignee = $chore->assigned_to;

        $updated = $this->chores->update($chore, $attributes);

        $reassigned = $updated->assigned_to !== null && $updated->assigned_to !== $previousAssignee;

        if ($reassigned && $updated->assigned_to !== $actor->id) {
            $updated->assignee?->notify(new ChoreAssignedNotification($updated, $actor));
        }

        if (! $reassigned) {
            $this->notifyInterestedParties($updated, $actor);
        }

        return ChoreData::fromModel($updated);
    }

    /** Tells the assignee and/or creator when someone else edits their chore, without notifying either twice. */
    private function notifyInterestedParties(Chore $chore, User $actor): void
    {
        $notified = [$actor->id];

        foreach ([$chore->assignee, $chore->creator] as $recipient) {
            if ($recipient !== null && ! in_array($recipient->id, $notified, true)) {
                $recipient->notify(new ChoreEditedNotification($chore, $actor));
                $notified[] = $recipient->id;
            }
        }
    }
}
