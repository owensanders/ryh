<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Data\Input\ChoreInputData;
use App\Enums\ChoreStatusEnum;
use App\Models\Chore;
use App\Models\Household;
use App\Models\User;
use Illuminate\Support\Collection;

interface ChoreRepositoryInterface
{
    /** @return Collection<int, Chore> */
    public function allFor(Household $household): Collection;

    public function moveTo(Chore $chore, ChoreStatusEnum $status): Chore;

    public function create(Household $household, ChoreInputData $chore, ChoreStatusEnum $status, User $creator): Chore;

    public function update(Chore $chore, ChoreInputData $changes): Chore;

    /** @return Collection<int, Chore> Assigned chores still to do, with their assignee. */
    public function outstandingAssigned(Household $household): Collection;

    public function delete(Chore $chore): void;

    /** Deletes the chores assigned to this member in this household — their other households keep theirs. */
    public function deleteAssignedTo(User $member, Household $household): void;
}
