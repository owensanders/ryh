<?php

declare(strict_types=1);

namespace App\UseCases\Chores;

use App\Contracts\Repositories\ChoreRepositoryInterface;
use App\Data\ChoreData;
use App\Data\Input\ChoreInputData;
use App\Enums\ChoreStatusEnum;
use App\Models\Household;
use App\Models\User;
use App\Notifications\ChoreAssignedNotification;

class CreateChoreUseCase
{
    public function __construct(private readonly ChoreRepositoryInterface $chores) {}

    public function execute(Household $household, ChoreInputData $input, User $actor): ChoreData
    {
        // New chores always start on the Today board.
        $chore = $this->chores->create($household, $input, ChoreStatusEnum::Today, $actor);

        if ($chore->assigned_to !== null && $chore->assigned_to !== $actor->id) {
            $chore->assignee?->notify(new ChoreAssignedNotification($chore, $actor));
        }

        return ChoreData::fromModel($chore);
    }
}
