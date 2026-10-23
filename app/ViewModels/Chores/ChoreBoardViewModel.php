<?php

declare(strict_types=1);

namespace App\ViewModels\Chores;

use App\Data\ChoreBoardScreenData;
use App\Data\ChoreColumnData;
use App\Data\ChoreData;
use App\Data\MemberData;
use App\Data\MemberScoreData;
use App\Enums\ChoreStatusEnum;
use App\Models\Chore;
use App\Models\Household;
use App\Models\User;
use Spatie\LaravelData\Lazy;

class ChoreBoardViewModel
{
    public function toData(Household $household, User $viewer): ChoreBoardScreenData
    {
        $chores = $household->chores()->forBoard()->get();

        $columns = array_map(
            fn (ChoreStatusEnum $status) => new ChoreColumnData(
                status: $status,
                title: $status->label(),
                count: $chores->where('status', $status)->count(),
                items: $chores->where('status', $status)->map(fn (Chore $chore) => ChoreData::fromModel($chore, $viewer))->values()->all(),
            ),
            ChoreStatusEnum::cases(),
        );

        return new ChoreBoardScreenData(
            columns: $columns,
            scores: $household->activeMembers()->get()
                ->map(fn (User $member) => MemberScoreData::make(
                    member: MemberData::fromModel($member),
                    done: $chores->where('assigned_to', $member->id)->where('status', ChoreStatusEnum::Done)->count(),
                    total: $chores->where('assigned_to', $member->id)->count(),
                ))
                ->values()
                ->all(),
            members: Lazy::closure(fn () => $household->members()->get()->map(MemberData::fromModel(...))->values()->all()),
            notifyChoresAvailableAt: Lazy::closure(fn () => $household->choreRemindersAvailableAt()?->toJSON()),
        );
    }
}
