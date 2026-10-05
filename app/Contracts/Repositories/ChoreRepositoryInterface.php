<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Enums\ChoreStatus;
use App\Models\Chore;
use App\Models\Household;
use App\Models\User;
use Illuminate\Support\Collection;

interface ChoreRepositoryInterface
{
    /** @return Collection<int, Chore> */
    public function allFor(Household $household): Collection;

    public function moveTo(Chore $chore, ChoreStatus $status): Chore;

    /** @param array<string, mixed> $attributes */
    public function create(Household $household, array $attributes): Chore;

    /** @param array<string, mixed> $attributes */
    public function update(Chore $chore, array $attributes): Chore;

    public function delete(Chore $chore): void;

    /** Deletes the chores assigned to this member in this household — their other households keep theirs. */
    public function deleteAssignedTo(User $member, Household $household): void;
}
