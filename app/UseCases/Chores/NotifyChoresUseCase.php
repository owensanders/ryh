<?php

declare(strict_types=1);

namespace App\UseCases\Chores;

use App\Contracts\Repositories\ChoreRepositoryInterface;
use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Exceptions\ChoreReminderCooldownException;
use App\Models\Household;
use App\Notifications\ChoresReminderNotification;

/** Reminds every member with at least one outstanding chore, then starts the household's cooldown. */
class NotifyChoresUseCase
{
    public function __construct(
        private readonly ChoreRepositoryInterface $chores,
        private readonly HouseholdRepositoryInterface $households,
    ) {}

    public function execute(Household $household): void
    {
        $availableAt = $household->choreRemindersAvailableAt();

        if ($availableAt !== null) {
            throw new ChoreReminderCooldownException($availableAt);
        }

        foreach ($this->chores->outstandingAssigned($household)->groupBy('assigned_to') as $chores) {
            $chores->first()?->assignee?->notify(new ChoresReminderNotification($household, $chores->count()));
        }

        $this->households->markChoreRemindersSent($household);
    }
}
