<?php

declare(strict_types=1);

namespace App\Actions\Chores;

use App\Data\ChoreData;
use App\Data\Input\ChoreInputData;
use App\Enums\ChoreStatusEnum;
use App\Models\Household;
use App\Models\User;
use App\Notifications\ChoreAssignedNotification;
use App\Repositories\ChoreRepository;

class CreateChoreAction
{
    public function __construct(private readonly ChoreRepository $chores) {}

    public function handle(Household $household, ChoreInputData $input, User $actor): ChoreData
    {
        // New chores always start on the Today board.
        $chore = $this->chores->create($household, $input, ChoreStatusEnum::Today, $actor);

        if ($chore->assigned_to !== null && $chore->assigned_to !== $actor->id) {
            $chore->assignee?->notify(new ChoreAssignedNotification($chore, $actor));
        }

        return ChoreData::fromModel($chore);
    }
}
